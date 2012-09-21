<?php
/*
* Rest plugin  model, based on work by kvz https://github.com/kvz/cakephp-rest-plugin/
*
* @author kamweti
*
*/
  class RestComponent extends Object{

    public $Controller;

    public $_settings = array();
    public $_active = true;

    function inititialize(&$Controller, $settings = array()){
    // called before the controller’s beforeFilter method.
      $this->Controller = &$Controller;

      $this->_settings = $_settings;
    }


    function startup(&$Controller){
      // called after the controller’s beforeFilter method but before the controller executes the current action handler
    }

    function shutdown(){
      //  called before output is sent to browser.
    }


    /*
    * Collects viewVars, reformats and makes them available
    *
    * @param <type> $Controller
    */
    function beforeRender(&$Controller) {
      if( !$this-_active() ) return;

      //setup controller so it can use the view from this plugin
      $this->Controller->view = 'Rest.' . $this->View(false);

    }



  }

 ?>
