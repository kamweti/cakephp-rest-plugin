<?php
/*
* Rest plugin  model, based on work by kvz https://github.com/kvz/cakephp-rest-plugin/
*
* @author kamweti
*
*/

App::import ( 'Sanitize' );

class RestController extends AppController{

  var $name = "Rest";
  var $debug_mode = false; // setting it to true will provide more verbose controller/action errors
  var $uses = array();
  var $components = array('RequestHandler');
  var $helpers = array('Rest.Bluntxml');
  var $_apioutput = 'xml'; //set default output type
  var $_logData = array();
  var $_settings = array(
    'log' => array(
      'enabled' => false, // enable after creating log table, view config/schema/rest_logs.sql
      'model' => 'RestLog'
    )
  );


  function beforeFilter(){
    parent::beforeFilter();

    //prepare log
    $this->_logData = array(
      'fullrequest' => $this->_fullRequest(),
      'httpmethod' => $_SERVER['REQUEST_METHOD'],
      'requested' => date('Y-m-d H:i:s'),
      'ip' => $_SERVER['REMOTE_ADDR'],
      'httpcode' => 200,
    );

  }

  function beforeRender(){
    parent::beforeRender();
    $this->autoLayout = false; // no layouts
  }

  function index(){

    $passedargs = func_get_args();

    if( !empty($passedargs) ) {

      $passedargs = Sanitize::clean( $passedargs , array( 'escape'=> false ));

      if( isset($passedargs[0]) ) {
        $_controller =$passedargs[0];
        $_controller = Inflector::camelize($_controller); //camel case

        // get controller
        if( App::import('Controller', $_controller) ) {

          $_controllerClassname = $_controller.'Controller';
          $_controller_instance = new $_controllerClassname;
          $_controller_instance->constructClasses(); // load model associations and components

          //find out if request is post or put and pass data
          if( $this->RequestHandler->isPost() || $this->RequestHandler->isPut() ) {
            if( !empty($this->params['form'])) {
              $_controller_instance->postData = Sanitize::clean( $this->params['form'], array( 'escape'=> false ) );
              $this->_logData['data_in'] = json_encode($_controller_instance->postData);
            }
          }

          //get action
          if( isset($passedargs[1]) ) {
            $action = 'api_'.$passedargs[1]; //concat since api actions are prefixed with api_

            $args = array_slice( $passedargs, 2, count($passedargs) - 1 ); // capture all arguments passed to the action

            $this->_runControllerAction( $_controller_instance, $action, $args );

          } else {
              if( in_array('api_index', $_controller_instance->methods ) ) {
                $this->_runControllerAction( $_controller_instance, 'api_index' ); //run the index action
              } else {
                // action does not exist in the controller
                if( $this->debug_mode == true ) {
                  // throw verbose error
                  $this->_error( __('Action "api_index" does not exist in Controller '.$_controller, true), 400 );
                } else {
                  $this->_error( __( 'Invalid API call', true ) , 400 );
                }
              }
          }
        } else {
          //controller does not exist
          $this->_error( __('Controller '.$_controller.' does not exist', true), 400 );
        }
      }
    } else {
      //no args passed, throw error
      $this->_error( __('Invalid API call', true), 400 );
    }
  }

  /**
  * Run Controller Action
  *
  */
  function _runControllerAction( $Controller, $action, $args = array() ){
    if(!isset($Controller) || !isset($action)) return;

    // log
    $this->_logData['controller'] = $Controller->name;
    $this->_logData['action'] = $action;

    // get exposed api actions
    // see whether this action is exposed
    if( in_array( $action, $Controller->methods ) ) {

      //arguments will be available in the controller with the normal $this->params['pass']
      $Controller->params['pass'] = $args;

      $Controller->{$action}(); //run the action, pass arguments and get view vars

      //capture errors and format pass them through the _error() handler
      if( array_key_exists('error', $Controller->viewVars)) {

        $httpCode = isset($Controller->viewVars['error']['httpCode']) ? $Controller->viewVars['error']['httpCode'] : 200;

        $this->_error( $Controller->viewVars['error']['message'], $Controller->viewVars['error']['errorCode'], $httpCode );

      } else {
        // all clear
        $this->set( 'response', $Controller->viewVars );

        $this->_logData['data_out'] = json_encode( $Controller->viewVars );

        $this->View();

      }
    } else {
        if( $this->debug_mode == true ) {
          $this->_error( __('Action "'.$action.'" does not exist in the controller', true), 400); // throw verbose error
        } else {
          $this->_error( __( 'Invalid API call', true ), 400 );
        }
    }


  }


  function View(){

    App::import ( 'Component', 'RequestHandler' );
    $RequestHandler = new RequestHandlerComponent();

    $pluginRoot = dirname(dirname(dirname(__FILE__)));

    if( array_key_exists('output', $this->params['url'])  ) {

      $this->params['url']['output'] = Sanitize::clean( $this->params['url']['output'], array( 'escape'=> false ) );

      // is output format in allowed formats?
      if( in_array($this->params['url']['output'], array('xml','json') ) ) {
        $this->_apioutput = $this->params['url']['output'];
      } else {
        unset($this->params['url']['output']); // avoid recursion
        $this->_error( __('Could not generate response, output format unknown', true), 400 );
        return;
      }
    }

    if(  $this->_apioutput == 'json' ) {


      header('Content-Type: text/javascript');
      $RequestHandler->setContent('json', 'text/javascript');
      $RequestHandler->respondAs('json');
      $this->render('/json');

    } elseif( $this->_apioutput == 'xml' ) {

      header('Content-Type: text/xml');
      $RequestHandler->setContent('xml', 'text/xml');
      $RequestHandler->respondAs('xml');
      $this->render('/xml');
    } else {
      $this->_apioutput = 'xml';
      $this->_error( __('Could not generate response, output format unknown', true), 400 );
    }

    $this->_logData['responded'] = date('Y-m-d H:i:s');
    $this->log();
  }

  function _error( $message = '' , $errorCode = null , $httpCode = 200 ) {

    $this->_logData['httpcode']  = $httpCode;
    $this->_logData['error']     = $message;
    $this->_logData['errorcode'] = $errorCode;

    $this->setHeader($httpCode);
    $this->set( 'response', array( 'request' => $this->_fullRequest(), 'message'=>$message, 'errorCode' => $errorCode ));
    $this->View();

  }


  /**
  * Return the full api request,
  * this will be extracted from the url
  * @return string
  */
  function _fullRequest(){
    //capture full client request for error reporting and logs
    $request_uri = str_replace( $this->base.'/rest', '', $_SERVER['REQUEST_URI'] ); // strips '/basename/rest' from request url
    return Sanitize::clean( $request_uri , array( 'escape'=> false ));
  }

  /*
  * Sets full response header given a response code
  * sets http code only for errors, successful responses return
  * a 200 - OK
  *
  * @param string HTTP status code
  * @return <type>
  */
  function setHeader( $code = 200 ){

    if( isset($code) ) {

      $status_codes = array(
        200 => 'OK',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Time-out',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Time-out',
      );

      $this->header( sprintf('HTTP/1.1 %s %s', $code, $status_codes[$code] ) );
    }

  }

  /**
  * Log api access activity
  *
  * will check if table is available
  * @return bool|array false if logging is not active logData
  */
  function log(){

    if( $this->_settings['log']['enabled'] == false ) return false; // won't log remember to create

    // initialize model
    if( ! isset($this->_RestLog) ) {
      $this->_RestLog = ClassRegistry::init( $this->_settings['log']['model'] );
    }

    //copy
    $this->RestLog = $this->_RestLog;

    // do insert
    $this->RestLog->create();
    $this->RestLog->save( array(
      $this->RestLog->alias => $this->_logData
    ));

    return $this->_logData;
  }

  function logModel(){
    if (!$this->_RestLog) {
      $this->_RestLog = ClassRegistry::init($this->_settings['log']['model']);
    }

    return $this->_RestLog;
  }

}
?>
