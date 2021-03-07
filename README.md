<h2 align="center">Laravel Easy Api</h2>
<h3 align="center">~ A simple admin panel for Laravel projects.</h3>
<br><br>
<p align="center"><img src="https://raw.githubusercontent.com/devs-ryan/img-storage/master/easy-admin-header.png"></p>
<p align="center">
<a target="_blank" href="https://laravel.com/"><img src="https://img.shields.io/badge/Built%20For-Laravel-orange" alt="Built For Laravel"></a>
<a target="_blank" href="https://packagist.org/packages/devsryan/laravel-easy-admin"><img src="https://img.shields.io/badge/Current%20Version-0.1.1-blue" alt="Version"></a>
<a target="_blank" href="https://packagist.org/packages/devsryan/laravel-easy-admin"><img src="https://img.shields.io/badge/License-MIT-green" alt="License"></a>
<a target="_blank" href="https://laravel.com/"><img src="https://img.shields.io/badge/Requires-Laravel%20%5E7.0-red" alt="Requires"></a>
</p>

## What is Laravel Easy Api

Laravel Easy Api is a back end UI designed for developers, root users with decent database knowledge or basic projects. It is not meant to serve as a complete Admin panel with full capabilities (see <a href="https://nova.laravel.com/">Lavavel Nova</a> if this is what you are looking for). On the contrary it is mean to act as a basic admin panel, with limited customizability, that can get up and running within minutes.

Laravel Easy Api leverages a powerful set of artisan commands to add/remove resources. This is combined with public files where functionality can be removed or added via commenting/uncommenting code which allows Easy Api to give basic ability for customization. If you need a quick and dirty admin panel for your project, this package is for you! :)


## Installation
- `composer require devs_ryan/laravel-easy-admin`
- `php artisan vendor:publish --tag=public --force`
- `php artisan migrate` (Your app is assumed to have a users table at this point)
- Access from <a href="https://github.com/devsryan/laravel-easy-admin">http(s)://your-project-url.com/easy-admin</a>

## Usage

#### Setting env variables
The following optional URL variables can be set in the Laravel .env file:
- `API_TOKEN` (will be sent with all requests to verify authentication Eg. `response = $axios.get('http://my-app-url/easy-api/index?api_token='.token')`)

#### Generate a random API_TOKEN
- TODO

#### Add a model resource to Easy Api
After running this command a CRUD resource will be added to the Easy Api UI for the model specified.
- `php artisan easy-api:add-model`
- Follow the prompts for namespace E.G. "App" and model name E.G. "User"
- This will generate a new file in the base projects app/EasyAdmin directory, where you can comment out any functionality you do not wish to provide to the Easy Api UI

#### Remove a model resource from Easy Api
- `php artisan easy-api:remove-model`
- Follow the prompts for namespace E.G. "App" and model name E.G. "User"
- This will remove the model from showing in the UI and delete the app/EasyAdmin file for it as well

#### Refresh a model resource in Easy Api
- `php artisan easy-api:refresh-model`
- Follow the prompts for namespace E.G. "App" and model name E.G. "User"
- This will reload the public file in the app/EasyAdmin directory to the default and load/remove any fields that have changed in the model

#### Add all model resources to Easy Api
This is not currently working, but on my TODO list.

#### Reset Easy Api
In case you would like to return Easy Api to the original state, use the command below.
- `php artisan easy-api:reset`

## Limitations
This admin panel assumes that you follow the standard Laravel naming conventions for models and database tables. If you create migrations/models using `php artisan make:model {ModelName} -m` it should work, otherwise it may not. 

The users table is expected to contain some fields that ship with the Laravel base install, such as `email` and `password`. 

All model resources must contain and `id` attribute in their database table for the routing to function.

## Licence
Laravel Easy Api is open-sourced software licensed under the [MIT license](LICENSE.md).
