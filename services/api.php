<?php

require_once("rest.php");

class API extends REST
{

  public $data = "";

  const DB_SERVER = "localhost";
  const DB_USER = "root";
  const DB_PASSWORD = "root";
  const DB = "to_do_list";

  private $db = NULL;
  private $pdo = NULL;

  public function __construct()
  {
    parent::__construct();
    $this->dbConnect();
  }

  /**
   *  Connect to Database.
   */
  private function dbConnect()
  {
    $this->mysqli = new mysqli(self::DB_SERVER, self::DB_USER, self::DB_PASSWORD, self::DB);
  }

  /**
   * Dynmically call the method based on the query string.
   */
  public function processApi()
  {

    $func = strtolower(trim(str_replace("/", "", $_REQUEST['x'])));
    if ((int) method_exists($this, $func) > 0)
    {
        $this->$func();
    }
    else
    {
      $this->response('', 404);
    }
  }

  /**
   * List all Task
   * @return
   *    Json with the loaded values of the BD or a
   *         message that does not have tasks.
   */
  private function listTask()
  {

    // Checks if the request is allowed.
    if ($this->get_request_method() != 'GET')
    {
      $this->response('', 406);
    }

    try
    {
      $query = "
        SELECT
          uuid,
          content,
          date_created,
          type
        FROM
          list
        ORDER BY sort_order asc
      ";

      // Executes the query to bring all the tasks.
      $tasks = $this->mysqli->query($query);
      if ($tasks->num_rows > 0)
      {

        $result = array();
        while ($row = $tasks->fetch_assoc())
        {
          $result[] = $row;
        }

        // Return the json with the loaded values of the BD.
        $this->response($this->json($result), 200);
      }

      $response = array(
        'status' => 'Success',
        'msg' => 'Wow. You have nothing else to do. Enjoy the rest of your day!'
      );

      // Return the json stating that do not have task.
      $this->response($this->json($response), 204);
    }
    catch(PDOException $e)
    {
      $response = array(
        'status' => 'Error',
        'msg' => 'An error occurred while trying to list the tasks.',
        'Error' => $e->getMessage(),
      );

      $this->response($this->json($response), 500);
    }
  }

  /**
   * Get information specific task.
   * @return
   *    Json with the task informationor a message
   *         that the task does not exist.
   */
  private function getTask()
  {

    // Checks if the request is allowed.
    if ($this->get_request_method() != 'GET')
    {
      $this->response('', 406);
    }

    try
    {
      $query = "
        SELECT
          uuid,
          content,
          type,
          done,
          sort_order
        FROM
          list
        WHERE
          uuid ='" . $this->_request['uuid'] . "'";

      $task = $this->mysqli->query($query);

      if ($task->num_rows > 0)
      {
        $this->response($this->json($task->fetch_assoc()), 200);
      }
      else
      {
        $response = array(
          'status' => 'Success',
          'msg' => "Are you a hacker or something? The task you were trying
                    to edit doesn't exist."
        );
        $this->response($this->json($response), 200);
      }
    }
    catch(PDOException $e)
    {
      $response = array(
        'status' => 'Error',
        'msg' => 'An error occurred while trying to recover the task information.',
        'Error' => $e->getMessage(),
      );

      $this->response($this->json($response), 500);
    }
  }

  /**
   * Create a new task.
   * @return
   *    Json with with a message that the task has
   *         been successfully created.
   */
  private function insertTask()
  {
    if ($this->get_request_method() != 'POST') {
      $this->response('', 406);
    }

    $data = json_decode(file_get_contents("php://input"), true);

    try {

      // The system can not allow empty save task.
      if (empty($data['content']))
      {

        $response = array(
          'status' => 'Error',
          'msg' => 'Bad move! Try removing the task instead of deleting
                    its content.',
        );
        $this->response($this->json($response), 406);
      }
      elseif ($data['type'] != 'shopping' && $data['type'] != 'work')
      { // Just types "shopping" and "work" are allowed by the system.

        $response = array(
          'status' => 'Error',
          'msg' => 'The task type you provided is not supported.
                    You can only use shopping or work.',
        );
        $this->response($this->json($response), 406);
      }
      else
      {

        // Prepares the information to be inserted in BD.
        $uuid = md5(uniqid(rand(), true));
        $created = date('Y-m-d');
        $has_done = !empty($data['has_done']) ? 1 : 0;
        $sort_order = ($data['sort_order']) ? $data['sort_order'] : 0;

        $query = "
          INSERT INTO
            list (uuid, type, content, sort_order, done, date_created)
          VALUES ("
            . "'" . $uuid . "', "
            . "'" . $data['type'] . "', "
            . "'" . $data['content'] . "', "
            . $sort_order . ", "
            . $has_done . ", "
            . "'" . $created . "')
        ";

        $this->mysqli->query($query);

        $response = array(
          'status' => 'Success',
          'msg' => 'Task successfully created :)'
        );

        // Return the json with a message that the task was created.
        $this->response($this->json($response), 200);
      }
    }
    catch(PDOException $e)
    {
      $response = array(
        'status' => 'Error',
        'msg' => 'An error occurred while trying to create the task.',
        'Error' => $e->getMessage(),
      );

      $this->response($this->json($response), 500);
    }
  }

  /**
   * Update a task.
   * @return
   *    Json with with a message that the task has
   *         been successfully created.
   */
  private function updateTask()
  {

    // Checks if the request is allowed.
    if ($this->get_request_method() != 'POST') {
      $this->response('', 406);
    }

    // Get the fields given in the request.
    $data = json_decode(file_get_contents("php://input"), true);

    try
    {

      //@TODO: Refatoring duplicate code.
      // The system can not allow empty save task.
      if (empty($data['content']))
      {

        $response = array(
          'status' => 'Error',
          'msg' => 'Bad move! Try removing the task instead of deleting its content.',
        );
        $this->response($this->json($response), 406);
      }
      elseif ($data['type'] != 'shopping' && $data['type'] != 'work')
      { // Just types "shopping" and "work" are allowed by the system.

        $response = array(
          'status' => 'Error',
          'msg' => 'The task type you provided is not supported.
                    You can only use shopping or work.',
        );
        $this->response($this->json($response), 406);
      }
      else
      {

        // Prepares the information to be updated task in BD.
        $has_done = !empty($data['has_done']) ? 1 : 0;
        $order = !empty($data['sort_order']) ? $data['sort_order'] : 0;

        $query = "
          UPDATE
            list SET
              type = '" . $data['type'] . "',
              content = '" . $data['content'] . "',
              sort_order = " . $order . ",
              done = '" . $has_done . "'
          WHERE
            uuid = '" . $data['uuid'] . "'
        ";

        $this->mysqli->query($query);

        $response = array(
          'status' => 'Success',
          'msg' => 'Task completed successfully :)'
        );

        // Return the json with a message that the task was updated.
        $this->response($this->json($response), 200);
      }
    }
    catch(PDOException $e)
    {
      $response = array(
        'status' => 'Error',
        'msg' => 'An error occurred while trying to update the task.',
        'Error' => $e->getMessage(),
      );

      $this->response($this->json($response), 500);
    }
  }

  /**
   * Delete a task.
   * @return
   *    Json with with a message that the task has
   *         been successfully created.
   */
  private function deleteTask()
  {

    // Checks if the request is allowed.
    if ($this->get_request_method() != 'DELETE') {
      $this->response('', 406);
    }

    // Get the fields given in the request.
    $data = json_decode(file_get_contents("php://input"), true);

    if (!empty($data['uuid'])) {
      try
      {
        $query = "
          DELETE FROM
            list
          WHERE
           uuid ='" . $data['uuid'] . "'";

        $result = $this->mysqli->query($query);

        // If the system does not find the record to be deleted,
        // it shall inform the task does not exist.
        if ($this->mysqli->affected_rows > 0)
        {
          $success = array(
            'status' => "Success",
            "msg" => "Successfully deleted one record."
          );
          $this->response($this->json($success),200);
        }
        else
        {
          $success = array(
            'status' => "Success",
            "msg" => "Good news! The task you were trying to delete
                       didn't even exist."
          );
          $this->response($this->json($success),200);
        }
      }
      catch(PDOException $e)
      {
        $response = array(
          'status' => 'Error',
          'msg' => 'An error occurred while trying to delete the task.',
          'Error' => $e->getMessage(),
        );

        $this->response($this->json($response), 500);
      }
    }
    else
    {
      $this->response('', 204);
    }
  }

  /**
   * Encode array into JSON.
   * @param $data array with the data to be presented to the user.
   * @return Json.
   */
  private function json($data)
  {
    if (is_array($data))
    {
      return json_encode($data);
    }
  }
}

// Initiiate Library.
$api = new API;
$api->processApi();

?>
