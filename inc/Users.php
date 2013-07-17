<?php

class Users extends F3instance {
    function profile() {
        $user_name = $this->get('PARAMS["user_name"]');
        $sql_query = 'select * from user where user_name =:user_name';
        $sql_query_params = array("user_name"=>$user_name);

        $ASKYOURGOVT_DB=F3::get('ASKYOURGOVT_DB');
        $ASKYOURGOVT_DB->exec($sql_query, $sql_query_params);

        $user_array = array();
        $array_all_question = array();

        foreach (F3::get('ASKYOURGOVT_DB->result') as $row){        
            $user_array['user_name'] = $row['user_name'];
            $user_array['user_full_name'] = $row['user_full_name'];
            $user_array['type'] = $row['type'];
            $user_array['website'] = $row['website'];
            $user_array['phone'] = $row['phone'];
            $user_array['address'] = $row['address'];
            $user_array['about'] = Markdown($row['about']);
            $user_array['picture_url'] = $row['picture_url'];
            $user_array['meta_questions_count'] = $row['meta_questions_count'];
            break;
        }




        //Get user details
        $sql_query = 'select * from questions, user, status where questions.publish_status = 1 ';
        $sql_query = $sql_query . ' and questions.user_name =:user_name';
        #$sql_query = $sql_query . ' and questions.department_id = departments.department_id';
        $sql_query = $sql_query . ' and questions.status_id = status.status_id';
        $sql_query = $sql_query . ' and questions.user_name = user.user_name';

        $sql_query_params = array("user_name"=>$user_name);

        //print $sql_query;
        $ASKYOURGOVT_DB->exec($sql_query, $sql_query_params);
        $offset = 0;
        foreach (F3::get('ASKYOURGOVT_DB->result') as $row){
            $single_question = array();
            $offset = $offset+1;
            $single_question['question_seq_num']= $offset;    
            $single_question['question_id']= $row["question_id"];    
            $single_question['question_title']= $row["question_title"];
            $single_question['question_short_title']= $row["question_short_title"];
            $single_question['question_text']=  ($row["question_text"]);    
            $single_question['user_name']= $row["user_name"];
            $single_question['user_full_name']= $row["user_full_name"];
            $single_question['date_asked']=   $row["date_asked"];
            #$single_question['department_id']=   $row["department_id"];
            #$single_question['department_name']=   $row["department_name"];
            $single_question['status_id']=   $row["status_id"];
            $single_question['status_name']=   $row["status_name"];
            $array_all_question[$row["question_id"]]   = $single_question;
            ///print $single_question['question_id'];
            if($offset >= 5){
                break;
            }
        }

        //Get question details
        $this->set('user_array',$user_array);
        $this->set('array_all_question',$array_all_question);

        if(sizeof($user_array) > 0){
            $this->set('title',$user_array['user_full_name']);
            //$this->set('sub_title','Profile of '.$user_array['user_full_name']);
        }else{
            $this->set('title','Not found');
            //$this->set('sub_title','Not found');            
        }

        $this->set('sub','sub_profile.html');
        $out=$this->render('basic/layout.html');        
        $this->set('sub_out_put',$out);
        $this->set('LANGUAGE','en-US');     
        echo $this->render('basic/main.html');

    }


function login(){
    if (isset($_POST['assertion'])) {
        $persona = new Persona();
        $result = $persona->verifyAssertion($_POST['assertion']);
        if ($result->status === 'okay') {
            $session = $this->set('SESSION["user_email"]', $result->email);
            echo $result->email;
        } else {
            header('HTTP/1.1 500 Internal Server Error');
            echo $result->reason;
        }
    }else{
        header('HTTP/1.1 500 Internal Server Error');
        echo "error";
    }
}

function logout(){
    $session = $this->set('SESSION["user_email"]', NULL);
}

}//end of Users class


class Persona
{
    /**
     * Scheme, hostname and port
     */
    protected $audience;

    /**
     * Constructs a new Persona (optionally specifying the audience)
     */
    public function __construct($audience = NULL)
    {
        $this->audience = $audience ?: $this->guessAudience();
    }

    /**
     * Verify the validity of the assertion received from the user
     *
     * @param string $assertion The assertion as received from the login dialog
     * @return object The response from the Persona online verifier
     */
    public function verifyAssertion($assertion)
    {
        $postdata = 'assertion=' . urlencode($assertion) . '&audience=' . urlencode($this->audience);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://verifier.login.persona.org/verify");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response);
    }

    /**
     * Guesses the audience from the web server configuration
     */
    protected function guessAudience()
    {
        $audience = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        $audience .= $_SERVER['SERVER_NAME'] . ':'.$_SERVER['SERVER_PORT'];
        return $audience;
    }
}




?>