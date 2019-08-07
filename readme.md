## Getting started with Twilio-sync using Laravel

In your time as a developer, you might find (or have found) yourself needing an easier and faster way to synchronize a user’s data across multiple devices. One way to do this would be to have a single source of state for your data, ideally in the cloud. Twilio provides a great service which can be used to manage and synchronize data in real-time across multiple devices and platforms.

In this tutorial we will take a look at how to make use of [Twilio Sync](https://www.twilio.com/sync) as a data store by building a To-do application using Laravel and Angular.

## Prerequisite

In order to follow this tutorial, you will need:

- Basic knowledge of Laravel
- [Laravel](https://laravel.com/docs/master) Installed on your local machine
- [Composer](https://getcomposer.org/) globally installed
- [Twilio Account](https://www.twilio.com/try-twilio)

## Getting started

In this tutorial, we will look at how to take advantage of Twilio Sync to manage our application data by building a Todo application using [Angular](https://angular.io/) and [Laravel](https://laravel.com/) for our Frontend and API respectively. We start by building our RESTful API which will be the backbone for our To-do application.

### Setting up Laravel Project

Firstly, we will create a new Laravel project either using the Laravel installer or composer.  For this tutorial, we’ll make use of the [Laravel Installer](https://laravel.com/docs/5.8#installation). If you don’t have it installed, you can check how to do so from the [Laravel documentation](https://laravel.com/docs/master).  To generate a fresh Laravel project, run this command in your terminal


    $ laravel new todo-sync 

Now change your working directory to `todo-sync` and install the Twilio SDK via composer :


    $ cd todo-sync
    $ composer require twilio/sdk 

If you don’t have composer installed on your PC you can do so by following the instructions [here](https://getcomposer.org/doc/00-intro.md).

After installing the [Twilio SDK](https://www.twilio.com/docs/libraries), we need to get our Twilio credentials from the Twilio dashboard. 
So head over to your [dashboard](https://www.twilio.com/console) and grab your `account_sid` and `auth_token`.

![](https://paper-attachments.dropbox.com/s_5B5FA98AD5606E04809864722A8F0F4EB8F518B0E2B10755A59A558A47AFF83E_1563979358324_Group+6.png)



Now navigate to the [Sync](https://www.twilio.com/console/sync/getting-started) section to create a new [Twilio](https://www.twilio.com/console/sync/services) [S](https://www.twilio.com/console/sync/services)[ync service](https://www.twilio.com/console/sync/services). This will serve as the state synchronization service for this tutorial. After successful creation of a service, take note of the generated `sid` as we will be making use of it shortly.

![](https://paper-attachments.dropbox.com/s_5B5FA98AD5606E04809864722A8F0F4EB8F518B0E2B10755A59A558A47AFF83E_1563798556825_Group+5.png)


Next step is to update `.env` file with our Twilio credentials. So open up `.env` located at the root of the project directory and add these values

    TWILIO_SID="INSERT YOUR TWILIO SID HERE"
    TWILIO_AUTH_TOKEN="INSERT YOUR TWILIO TOKEN HERE"
    TWILIO_SYNC_SID="INSERT YOUR TWILIO SYNC SERVICE SID"

## Building Todo API

We have successfully setup our Laravel project with Twilio SDK installed, now let’s move on to building the API for our Todo application.
First, let’s create a new controller called `TodoController`, this will be our main controller for the API. Fire up a terminal in the working directory and run the following:

    $ php artisan make:controller TodoController

this will generate a file in your `app/Http/Controllers/` directory called `TodoController.php`

### Creating Todo

Next, open up `TodoController.php` and add the method below. This method creates a Todo item and stores it in our Twillo Sync service:

     public function createTodo(Request $request)
        {
            $validatedData = $request->validate([
                'body' => 'required',
            ]);
            $account_sid = getenv("TWILIO_SID");
            $auth_token = getenv("TWILIO_AUTH_TOKEN");
            $sync_sid = getenv("TWILIO_SYNC_SID");
            $client = new Client($account_sid, $auth_token);
            $newTodo = $client->sync->v1->services($sync_sid)
                ->documents
                ->create(array(
                    "data" => array(
                        "created_at" => now(),
                        "body" => $validatedData['body'],
                        "isDone" => false,
                    ),
                ));
            $todo = $newTodo->data;
            $todo["sid"] = $newTodo->sid;
            return response()
                ->json($todo);
        }

Now, let’s break down what is happening in the code above. After validating the data coming into our function via the `$request` property, we get our stored Twilio credentials from the environment variables using the built-in PHP `[getenv()](http://php.net/manual/en/function.getenv.php)` function and instantiate a new Twilio client using the credentials. We access the `sync`  service from the instance of the Twilio client i.e:

    $client->sync->v1->services

passing in our Twilio Sync service `sid` stored in the `.env` file earlier in this tutorial, then we create a new document on the service instance using `documents→create()`. The `create()` method takes a number of properties in an [associative array](https://www.php.net/manual/en/language.types.array.php) among which is the `data` property to which we assign the JSON data to be stored in this document i.e:

    "data" => array(
                "created_at" => now(),
                "body" => $validatedData['body'],
                "isDone" => false,
              ),

We have three properties in our `JSON` array: `created_at`, `body`  - body of todo item, and `isDone` - a `boolean` flag to indicate if todo is completed or not. After successful creation of our Todo item we return a `JSON`  response of the newly created Todo document:

    $todo = $newTodo->data;
    $todo["sid"] = $newTodo->sid;
    return response()->json($todo);

***Note:** We are returning a `JSON` data instead of a `view` because we are building our frontend separate from our Laravel project.*

### Retrieving Stored Todo Items

After successful creation of a todo item, the next thing will be to retrieve our stored items. We can easily accomplish this by using the `read()` method on the Twilio Sync service instance. In the `TodoController.php`  add the following method:

     public function getTodo()
        {
            $account_sid = getenv("TWILIO_SID");
            $auth_token = getenv("TWILIO_AUTH_TOKEN");
            $sync_sid = getenv("TWILIO_SYNC_SID");
            $client = new Client($account_sid, $auth_token);
            $documents = $client->sync->v1->services($sync_sid)
                ->documents->read();
            $data = [];
            foreach ($documents as $record) {
                $todo = $record->data;
                $todo['sid'] = $record->sid;
                array_push($data, $todo);
            }
            return response()
                ->json($data);
        }

Let’s take a closer look at what’s new above:

    $documents = $client->sync->v1->services($sync_sid)
                ->documents->read();
            $data = [];
            foreach ($documents as $record) {
                $todo = $record->data;
                $todo['sid'] = $record->sid;
                array_push($data, $todo);
            }

We are making use of the `read()` method on the Sync service instance which returns all documents stored in this service instance. We loop through the returned documents using a [foreach](https://www.php.net/manual/en/control-structures.foreach.php) and return only the `data` and `sid` of the document while pushing it into a new array which we later return as a `JSON` data.

***Note:***

- *`read()` by default,  returns the first 50 documents in the service instance but you can adjust this to your needs by passing in a `$limit` and `$pageSize` parameter.*
- *We are storing the document `sid` as we will be making use as a unique way to update or delete an item later on*.

### Updating a Todo Item

Next, let’s make it possible to update an item on our todo list. To update an existing document on our Sync service, we will make use of the document `sid` - which is a unique identifier for each document on a Sync service - to access the document instance and call the `update()` method passing in the data to be updated on the document. Add the following method to the `TodoController.php`:

      public function updateTodo(Request $request, $sid)
        {
            $validatedData = $request->validate([
                'body' => 'required',
                'isDone' => 'required',
                'created_at' => 'required',
                'sid' => 'required',
            ]);
            $account_sid = getenv("TWILIO_SID");
            $auth_token = getenv("TWILIO_AUTH_TOKEN");
            $sync_sid = getenv("TWILIO_SYNC_SID");
            $client = new Client($account_sid, $auth_token);
            $newTodo = $client->sync->v1->services($sync_sid)
                ->documents($sid)
                ->update(array(
                    "data" => array(
                        "sid" => $validatedData["sid"],
                        "body" => $validatedData['body'],
                        "isDone" => $validatedData["isDone"],
                        "created_at" => $validatedData["created_at"],
                    ),
                ));
            return response()
                ->json($newTodo->data);
        }

Taking a closer look at the new code below:

    $newTodo = $client->sync->v1->services($sync_sid)
                ->documents($sid)
                ->update(array(
                    "data" => array(
                        "sid" => $validatedData["sid"],
                        "body" => $validatedData['body'],
                        "isDone" => $validatedData["isDone"],
                        "created_at" => $validatedData["created_at"],
                    ),
                ));

The `documents()` method takes a parameter of the document `$sid` and returns the document instance if found, after which we call the `update()` method on the document instance. The `update()`  method takes in an associative array with `key`  of `data`  which we assign the `JSON` data to be updated to. Once the update is done, we return the now updated document data as a `JSON` response.

### Deleting a Todo Item

Lastly, let’s implement the delete method. Add the following method to the `TodoController.php`

    public function deleteTodo($sid)
        {
            $account_sid = getenv("TWILIO_SID");
            $auth_token = getenv("TWILIO_AUTH_TOKEN");
            $sync_sid = getenv("TWILIO_SYNC_SID");
            $client = new Client($account_sid, $auth_token);
            $client->sync->v1->services($sync_sid)
                ->documents($sid)
                ->delete();
            return response()
                ->json(["message" => "Todo sucessfully deleted!"]);
        }

Deleting from a document model is quite straightforward, after accessing the document instance with the document `sid` we call the `delete()` method which deletes the document from the Sync service i.e:

    $client->sync->v1->services($sync_sid)
                ->documents($sid)
                ->delete();

After which we return a `JSON` response with a `message`.

### Creating Routes

At this point, we have successfully built a basic CRUD API for our todo application. Now let’s create the routes for our API. 
Usually, when building a Laravel application we would place our application routes in the `routes/web.php`  file which will allow us access our application by `http://todo-app.test/todo` and also routes in this file are assigned the `web` middleware group, which provides features like session state and CSRF protection. But since we are building an API, we would have to place our routes in the `routes/api.php`  file which will prefix all our routes with `/api`  and also assigns  the `api` middleware group, which provides features like throttling and bindings and also allows our routes to be stateless.  Open up `routes/api.php`  and make the following changes:

    <?php
    use Illuminate\Http\Request;
    /*
    |--------------------------------------------------------------------------
    | API Routes
    |--------------------------------------------------------------------------
    |
    | Here is where you can register API routes for your application. These
    | routes are loaded by the RouteServiceProvider within a group which
    | is assigned the "api" middleware group. Enjoy building your API!
    |
    */
    Route::get('/todo', "TodoController@getTodo");
    Route::post('/todo', "TodoController@createTodo");
    Route::put('/todo/{sid}', "TodoController@updateTodo");
    Route::delete('/todo/{sid}', "TodoController@deleteTodo");
    


## Building the Frontend

We have successfully built our API application using Laravel with Twilio Sync for state management. Now let’s build our frontend application to interface with the API. We will be making use of [Angular](https://angular.io/) as our frontend framework in this tutorial.

To get started, we will scaffold our Angular project using the [Angular CLI](https://angular.io/cli). If you don’t have the Angular CLI already installed, simply run the following to get it installed via `[npm](https://www.npmjs.com/)`:

    $ npm install -g @angular/cli

This command will install the Angular CLI globally. You can check out the [official documentation](https://angular.io/cli) to learn more about angular-cli. Open up a new terminal and run the following to generate a new Angular application:

    $ ng new todo-app

This will generate a base Angular project for us which we will be making necessary adjustments to shortly.

***Note:*** *The above command should be run outside the Laravel project directory as it’s a standalone Angular application.*

### Adding External Resources

For the sake of this tutorial, we won’t focus much on styling. To expedite this need, we will be making use of [Bootstrap](https://getbootstrap.com/) and [Font-awesome](https://fontawesome.com/) for styling and icons respectively. Open the newly created Angular application in your favorite IDE and make the following changes in `index.html` to add the CDN links for both Bootstrap and Font-awesome:

    <!doctype html>
    <html lang="en">
    <head>
      <meta charset="utf-8">
      <title>TodoApp</title>
      <base href="/">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <link rel="icon" type="image/x-icon" href="favicon.ico">
      <!-- Bootstrap CDN -->
      <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
      <!-- Font-awesome CDN -->
      <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.9.0/css/all.min.css" rel="stylesheet">
    </head>
    <body>
      <app-root></app-root>
    </body>
    </html>
    

### Connecting to Backend API

We will now connect our Angular application to our API created in Laravel. We can achieve this by making HTTP requests to our Laravel application. In Angular, data access is processed in a [service](https://angular.io/guide/architecture-services) class. We can easily generate a service using the Angular CLI. Run the following command to generate a new service in a `/services` directory:

    $ ng generate service /services/TodoService

This will create a new service in `src/app/services/` named `todo-service.service.ts` and also inject it into our application as a [singleton service](https://angular.io/guide/singleton-services). Proceed to make the following changes to the `todo-service.service.ts` file:

    import { Injectable } from '@angular/core';
    import { HttpClient } from '@angular/common/http';
    @Injectable({
      providedIn: 'root'
    })
    export class TodoService {
      private baseUrl = 'http://127.0.0.1:8000/api/todo';
      constructor(private http: HttpClient) { }
    
      createTodo(body) {
        return this.http.post(this.baseUrl, body);
      }
    
      updateTodo(sid: string, todo) {
        return this.http.put(`${this.baseUrl}/${sid}`, { ...todo });
      }
    
      deleteTodo(sid: string) {
        return this.http.delete(`${this.baseUrl}/${sid}`);
      }
    
      getTodos() {
        return this.http.get(this.baseUrl);
      }
    }
    
Here we created methods for all available actions on our API which is a basic CRUD operation.  

***Note:*** 

- *`[HttpClient](https://angular.io/guide/http)` is Angular's mechanism for communicating with a remote server over HTTP.*
- *You will need to add the `[HttpClientModule](https://angular.io/guide/feature-modules)` into your `src/app/app.module.ts` `imports` array to ensure it is available in the application.*

### Creating User Interface

Now we need to create a way for users to create, view, update the state of, and also delete a Todo item. To accomplish this, we start by making the following changes to the `src/app/app.component.ts`:

    import { TodoService } from './services/todo-service.service';
    import { Component } from '@angular/core';
    @Component({
      selector: 'app-root',
      templateUrl: './app.component.html',
      styleUrls: ['./app.component.scss']
    })
    export class AppComponent {
      title = 'todo-app';
      todos: { body: string, created_at: Date, sid: string, isDone: boolean }[] = [];
      itemValue: string;
      constructor(private todoService: TodoService) {
        this.getTodos(); // Get todos on component intialization
      }
      getTodos() {
        this.todoService.getTodos()
          .subscribe((res: any) => {
            this.todos = res;
            this.todos.reverse();
            console.log(res);
          }, error => {
            console.error(error);
          });
      }
      createTodo() {
        this.todoService.createTodo({ body: this.itemValue })
          .subscribe((res: any) => {
            this.todos.unshift(res);
            this.itemValue = '';
            console.log(res);
          }, error => {
            console.error(error);
          });
      }
      deleteTodo(todoSid) {
        this.todoService.deleteTodo(todoSid)
          .subscribe((res: any) => {
            this.todos = this.todos.filter(todo => todo.sid !== todoSid);
            this.itemValue = '';
            console.log(res);
          }, error => {
            console.error(error);
          });
      }
      updateTodo(todo) {
        this.todoService.updateTodo(todo.sid, todo)
          .subscribe((res: any) => {
            this.itemValue = '';
            console.log(res);
          }, error => {
            console.error(error);
          });
      }
    }
    
Next let’s create the view with a form input for creating a Todo and also different sections for listing uncompleted and completed Todos. Now open up `src/app/app.component.html` and make the following changes:

    <!--The content below is only a placeholder and can be replaced.-->
    <div class="container my-5">
      <h2>
        Welcome to {{ title }}!
       </h2>
      <form>
        <div class="form-group">
          <label for="formGroupExampleInput">Enter Todo Item</label>
          <input [(ngModel)]="itemValue" name="item" type="text" class="form-control" placeholder="Buy beans..">
        </div>service
        <button [disabled]="!itemValue?.length" (click)="createTodo()" type="button" class="btn btn-primary btn-block">Add
          Item</button>
      </form>
      <div class="todo-list my-5">
        <h3>Your To-do list: </h3>
      <ng-template *ngFor="let todo of todos" [ngIf]="!todo.isDone">
        <div class="input-group mb-3">
          <div class="input-group-prepend">
            <div class="input-group-text">
              <input type="checkbox" [(ngModel)]="todo.isDone" (change)="updateTodo(todo)">
            </div>
          </div>
          <input [(ngModel)]="todo.body" [value]="todo.body" type="text" class="form-control">
          <div class="input-group-append" id="button-addon4">
            <button (click)="updateTodo(todo)" class="btn btn-success" type="button">
              <i class="far fa-edit"></i>
            </button>
            <button (click)="deleteTodo(todo.sid)" class="btn btn-danger" type="button">
              <i class="fa fa-trash"></i>
            </button>
          </div>
        </div>
      </ng-template>
      </div>
      <div class="todo-list my-5">
        <h3>Your Completed To-do(s): </h3>
        <ng-template  *ngFor="let todo of todos" [ngIf]="todo.isDone">
          <div  class="input-group mb-3">
            <div class="input-group-prepend">
              <div class="input-group-text">
                <input type="checkbox" [(ngModel)]="todo.isDone" (change)="updateTodo(todo)">
              </div>
            </div>
            <input disabled [(ngModel)]="todo.body" [value]="todo.body" type="text" class="form-control">
            <div class="input-group-append" id="button-addon4">
              <button (click)="deleteTodo(todo.sid)" class="btn btn-danger" type="button">
                <i class="fa fa-trash"></i>
              </button>
            </div>
          </div>
        </ng-template>
      </div>
    </div>
    


## Testing Our Application

We have successfully completed our To-do application, now let’s test our implementations. To test, let’s start up our Laravel server. Fire up a terminal in the Laravel project directory and run:

    $ php artisan serve

Next, let’s run our Angular application. Change your working directory to the Angular project directory and run:

    $ ng serve --open

***Note:** The `--open` flag tells Angular CLI to launch the angular application in your default browser after compiling.*

If the above command runs successfully, proceed to open up a web browser and navigate to `http://localhost:4200/`. You should be greeted with a page similar to this:

![View of todo application](https://paper-attachments.dropbox.com/s_5B5FA98AD5606E04809864722A8F0F4EB8F518B0E2B10755A59A558A47AFF83E_1563922381854_Screenshot+from+2019-07-23+23-52-42.png)


Now go ahead and add some items to your to-do and try updating the state of an item. Here’s a screenshot of how your application should look after adding a Todo item:

![Todo application with uncompleted and completed todo items](https://paper-attachments.dropbox.com/s_5B5FA98AD5606E04809864722A8F0F4EB8F518B0E2B10755A59A558A47AFF83E_1563922745453_Screenshot+from+2019-07-23+23-58-00.png)

## Conclusion

Great! We have successfully built our To-do application while learning how to make use of Twilio Sync Service for state management in a Laravel application. Additionally, this tutorial taught us how to perform basic CRUD operations on a Twilio Sync service, how to build a RESTful API using Laravel and successfully integrate it with an Angular Client.

If you would like to take a look at the complete source code for this tutorial, you can find both the [Laravel Application](https://github.com/thecodearcher/todo-application-with--twilio-sync) and [Angular Application](https://github.com/thecodearcher/todo-application-with--twilio-sync-angular) respectively on Github. 

You can also take a look at other cool things you can accomplish with [Twilio Sync](https://www.twilio.com/sync) including real-time communication between browsers, mobile devices, and the cloud. 

I’d love to answer any question(s) you might have concerning this tutorial. You can reach me via

- Email: [brian.iyoha@gmail.com](mailto:brian.iyoha@gmail.com)
- Twitter: [thecodearcher](https://twitter.com/thecodearcher)
- GitHub: [thecodearcher](https://github.com/thecodearcher)


