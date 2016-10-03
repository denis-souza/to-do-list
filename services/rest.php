<?php
  class REST
  {

    public $_allow = array();
    public $_content_type = "application/json";
    public $_request = array();

    private $_method = "";
    private $_code = 200;

    public function __construct()
    {
      $this->inputs();
    }

   /**
    * @return HTTP_REFERER.
    */
    public function get_referer()
    {
      return $_SERVER['HTTP_REFERER'];
    }

    /**
     * Delivery to the front end information processed.
     * @param $data json Json with status information and message request.
     * @param $status int Status code of request.
     *
     * @return json With status information and message request.
     */
    public function response($data, $status)
    {
      $this->_code = ($status) ? $status : 200;
      $this->set_headers();

      print_r($data);
      exit;
    }

    /**
     * Get the status of the request.
     * @return string Message status code.
     */
    private function get_status_message()
    {
      $status = array(
        200 => 'OK',
        201 => 'Created',
        204 => 'No Content',
        404 => 'Not Found',
        406 => 'Not Acceptable'
      );

      return ($status[$this->_code]) ? $status[$this->_code] : $status[500];
    }

   /**
    * Get the type request.
    * @return string Type request.
    */
    public function get_request_method()
    {
      return $_SERVER['REQUEST_METHOD'];
    }

    /**
     * Defines the inputs to the methods of request.
     */
    private function inputs()
    {
      switch($this->get_request_method())
      {
        case "POST":
          $this->_request = $this->cleanInputs($_POST);
          break;
        case "GET":
        case "DELETE":
          $this->_request = $this->cleanInputs($_GET);
          break;
        case "PUT":
          parse_str(file_get_contents("php://input"),$this->_request);
          $this->_request = $this->cleanInputs($this->_request);
          break;
        default:
          $this->response('',406);
          break;
      }
    }

    /**
     * Clear information from informed fields.
     */
    private function cleanInputs($data)
    {

      $clean_input = array();
      if (is_array($data))
      {
        foreach ($data as $k => $v)
        {
          $clean_input[$k] = $this->cleanInputs($v);
        }
      }
      else
      {
        if (get_magic_quotes_gpc())
        {
          $data = trim(stripslashes($data));
        }

        $data = strip_tags($data);
        $clean_input = trim($data);
      }

      return $clean_input;
    }

    /**
     * Define headers to request.
     */
    private function set_headers()
    {
      header("HTTP/1.1 ".$this->_code." ".$this->get_status_message());
      header("Content-Type:".$this->_content_type);
    }
  }
?>
