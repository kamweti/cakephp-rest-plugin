# CakePHP Rest Plugin

Painless REST server Plugin for CakePHP

### Features

This plugin takes your controller actions prefixed with 'api_' and gathers viewVars and reformats to `json` or `xml` for output

So if you have a controller `recipes` with an action `api_create` you'll have `/rest/recipes/create` running in no time


####Why prefixed actions?

Prefixed actions will add add context in the controller allowing you to compose viewVars api calls exclusively.

## Requirements
* PHP 5.2.6+ or the PECL json package
* CakePHP 1.2/1.3

## Installing

Clone or download the plugin into your plugin path `app/plugins`

Open your `app/config/routes.php` and add this line

      Router::connect('/rest/*', array('controller' => 'rest', 'action' => 'index', 'plugin' => 'rest' ) );

This line routes all api calls to the plugin


## Usage

A simple recipes controller which fetches data from the Recipes model.

  ```php
  <?php
    class RecipesController extends AppController {

      var $name = 'Recipes';

      function index() {
      }

      function api_view($id = null) {
      }

      function api_index() {
        $this->Recipe->recursive = 0;
        $this->set('recipes', $this->paginate());
      }

      function api_view() {
        if (!empty($this->params)) {
          $id = $this->params[0];
          $this->set('recipe', $this->Recipe->read(null, $id));
        }
        $this->set( 'error', array( 'message'=>__('Recipe not found', true), 'httpCode' => 404 ) );
      }

    }
  ?>
  ```
Action `api_index` maps to the call `/rest/recipes/`
Action `api_view` maps to the call `/rest/recipes/view/3`

Note: actions prefixed with `api_` are automatically exposed to the api

#### JSON / XML Support
Default api output format is set to `xml` , an output format argument is used to switch between formats `rest/recipes/view/3?output=json`


## License

Licensed under [MIT](http://www.opensource.org/licenses/mit-license.php).


### Authors

[Kamweti Muriuki](http://kamweti.tumblr.com/) based on work by [kvz](http://github.com/kvz)

### Roadmap

* ~~Support for multiple arguments in url~~
* ~~Support error output levels, verbose, reserved~~
* ~~Log all requests and responses~~
* More Tests
* Port to Cake 2.0
* Better documentation
* Authentication
* Callbacks
