<?php

/*
	This class is responsible for all of the communication between
	suitecrm and the 
	models used in the client.


	All data returns to the client should be in the form of on of the ClientModels found in
	App\ClientModels


	Date: June 12th 2017
*/

namespace SuiteCORE\SuiteModelController;

class SuiteClient 
{

	private $url;
	private $password;
	private $username;
	private $session_id;

  function __construct($URL, $user, $pass)
	{

		$this->url = $URL;
		$this->password = $pass;
		$this->username = $user;

		$this->session_id = $this->getSessionID($URL, $user, $pass);
		
	}

	


		//function to make cURL request
  private function call($method, $parameters, $url)
  {
    ob_start();
    $curl_request = curl_init();

     curl_setopt($curl_request, CURLOPT_URL, $url);
     curl_setopt($curl_request, CURLOPT_POST, 1);
     curl_setopt($curl_request, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
     curl_setopt($curl_request, CURLOPT_HEADER, 1);
     curl_setopt($curl_request, CURLOPT_SSL_VERIFYPEER, 0);
     curl_setopt($curl_request, CURLOPT_RETURNTRANSFER, 1);
     curl_setopt($curl_request, CURLOPT_FOLLOWLOCATION, 0);

     $jsonEncodedData = json_encode($parameters);

     $post = array(
             "method" => $method,
             "input_type" => "JSON",
             "response_type" => "JSON",
             "rest_data" => $jsonEncodedData
        );

     curl_setopt($curl_request, CURLOPT_POSTFIELDS, $post);
     $result = curl_exec($curl_request);
     curl_close($curl_request);

     $result = explode("\r\n\r\n", $result, 2);
     $response = json_decode($result[1]);
     ob_end_flush();

     return $response;
   }


    public function getModelDataSingle($module_name, $id, $fields, $linked_fields)
    {


               $get_entry_parameters = array(
                    //session id
                    'session' => $this->session_id,

                    //The name of the module from which to retrieve records
                    'module_name' => $module_name,

                    //The ID of the record to retrieve.
                    'id' => $id,

                //The list of fields to be returned in the results
                'select_fields' => $fields,
                //A list of link names and the fields to be returned for each link name
                'link_name_to_fields_array' =>$linked_fields,
        
                    //Flag the record as a recently viewed item
                    'track_view' => true,
            );


        return $this->call('get_entry', $get_entry_parameters, $this->url);
    }

	
    public function loginUser($username, $password)
    {


        $login_parameters = array(
    //user authentication
    "user_auth" => array(
        "user_name" => $username,
        "password" => md5($password),
    ),

    //application name
    "application_name" => "My Application",

    //name value list for 'language' and 'notifyonsave'
    "name_value_list" => array(
        array(
            'name' => 'language',
            'value' => 'en_us',
        ),

        array(
            'name' => 'notifyonsave',
            'value' => true
        ),
    ),
);


        return  $this->call('login', $login_parameters, $this->url);
    }

	/*
		Gets the various map data associated
		with the session_id and the url.
	
		All of these parameters should be strings
	
		$module_name - the name of the SuiteCRM model for the corresponding obejct we're
		fetching
		$query - the database where clause, filtering the models we actually want
		order_by - the order_by database clause on the data we're fetching
		$fields - the name of the fields in the database for the object we're fetching
		$max_resutls - the maximum number of results to return
		
	*/
	public function getModelData($module_name, $query, $order_by, $fields, $max_results, $linked_fields)
	{


    	$get_entry_list_result = $this->retrieveEntryList($module_name, $query, $order_by, $fields, $max_results, $linked_fields);


		return $get_entry_list_result;
	}



    public function retrieveRelatedRecords($module_name, $module_id, $relationship_name, $query, $order_by, $fields, $max_results, $linked_fields)
    {

        $get_relationships_parameters = array(
            //session id
            'session' => $this->session_id,

            //The name of the module from which to retrieve records.
            'module_name' => $module_name,

            //The ID of the specified module bean.
            'module_id' => $module_id,

            //The relationship name of the linked field from which to return records.
            'link_field_name' => $relationship_name,

            //The portion of the WHERE clause from the SQL statement used to find the related items.
            'related_module_query' => $query,

            //The related fields to be returned.
            'related_fields' => $fields,

            //For every related bean returned, specify link field names to field information.
            'related_module_link_name_to_fields_array' => $linked_fields,

            //To exclude deleted records
            'deleted'=> 0,

            //order by
            'order_by' => $order_by,

            //offset
            'offset' => 0,

            //limit
            'limit' => $max_results );

         return $this->call('get_relationships', $get_relationships_parameters, $this->url);

    }

    protected function recordValToSugarVal($key, $val)
    {
        return array( "name" => $key,
                      "value" => $val

            );
    }

    public function saveNewRecord($module_name, $record_vals)
    {
        $id_array = array();

        return $this->saveRecord($module_name, $id_array, $record_vals);
    }

    public function deleteExistingRecord($module_name, $id)
    {

        $record_vals = array("deleted" => '1');
        //return "HELLO";
        return $this->saveExistingRecord($module_name, $id, $record_vals);


    }

    /**
    *
    *   @param $module_name - the suite name of the module
    *   @param module_id - the id of the record we are linking from
    *       Ex: if we wanted to put an set of emails on an employee, this would be the employee id
    *   @param related_id - the id of the field we are relating
    *   @param related_field_name - the name of the field we are relating
    **/
    public function setRelationship($module_name, $module_id, $related_id, $related_field_name)
    {

        $set_relationship_parameters = array(
             //session id
            'session' => $this->session_id,

            //The name of the module.
            'module_name' => $module_name,

            //The ID of the specified module bean.
            'module_id' => $module_id,

            //The relationship name of the linked field from which to relate records.
            'link_field_name' => $related_field_name,

            //The list of record ids to relate
            'related_ids' => array(
                $related_id,
            ),

            //Sets the value for relationship based fields
            'name_value_list' => array(),

            //Whether or not to delete the relationship. 0:create, 1:delete
            'delete'=> 0,
        );

        //echo "Module ID: " . $module_id;

        //return print_r($set_relationship_parameters, true);
        return $this->call('set_relationship', $set_relationship_parameters, $this->url);

    }


    /**
    *
    * Saves a record that currently exists in the database.
    * Doesn't save the relationships.
    * @param module_name - the name of the module for the record we are saving
    * @param record_id - the id of the record we are saving
    * @param record_vals - name, value pair array where the names correspond to the field names
    * @return the response from the server
    * 
    */
    public function saveExistingRecord($module_name, $id,  $record_vals)
    {

        $id_array = array(array(
                    "name" => "id",
                    "value" => $id
                    ));

        return $this->saveRecord($module_name, $id_array, $record_vals);

    }

    private function saveRecord($module_name, $id_array, $record_vals)
    {

        $name_values = array_map([$this, "recordValToSugarVal"], array_keys($record_vals), array_values($record_vals) );


        $vals = array_merge($id_array, $name_values);


         $set_entry_parameters = 
         array(
            //session id
            "session" => $this->session_id,

            //The name of the module from which to retrieve records.
            "module_name" => $module_name,

            //Record attributes
            "name_value_list" => $vals, );
        

          //echo "Client SAVE: " . print_r($vals, true);
          return  $this->call('set_entry', $set_entry_parameters, $this->url);
    }


    public function retrieveEntryList($module_name, $query, $order_by, $fields, $max_results, $linked_fields)
    {

        

             //get list of records --------------------------------
        $get_entry_list_parameters = array(

         //session id
         'session' => $this->session_id,

         //The name of the module from which to retrieve records
         'module_name' => $module_name,

         //The SQL WHERE clause without the word "where".
         'query' => $query,

         //The SQL ORDER BY clause without the phrase "order by".
         'order_by' => $order_by,

         //The record offset from which to start.
         'offset' => '0',

         //Optional. A list of fields to include in the results.
         'select_fields' => $fields,

         /*
         A list of link names and the fields to be returned for each link name.
         Example: 'link_name_to_fields_array' => array(array('name' => 'email_addresses', 'value' => array('id', 'email_address', 'opt_out', 'primary_address')))
         */
         'link_name_to_fields_array' => $linked_fields,

         //The maximum number of results to return.
         'max_results' => $max_results,

         //To exclude deleted records
         'deleted' => '0',

         //If only records marked as favorites should be returned.
         'Favorites' => false,
        );

      
        return $this->call('get_entry_list', $get_entry_list_parameters, $this->url);

    }

	/*

		Using the suitecrm $url,
			and the $username and $password
			
			returns the session id, associated 
			with a particular login session.

	*/
	private function getSessionID($url, $username, $password)
	{

		 $login_parameters = array(
         	"user_auth" => array(
              "user_name" => $username,
              "password" => md5($password),
              "version" => "1"
         	),
         	"application_name" => "RestTest",
         	"name_value_list" => array(),
    	);


		$login_result = $this->call("login", $login_parameters, $url);

		return $login_result->id;
	}

	
}

