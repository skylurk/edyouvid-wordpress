<?php
// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;

if(!class_exists('vxcf_hubspot_api')){
    
class vxcf_hubspot_api extends vxcf_hubspot{
  
  public $info=array(); // info
  public $error= "";
  public $timeout=30;
  public $objects=array('Contact'=>'contacts','Ticket'=>'tickets','Deal'=>'deals','Company'=>'companies','Task'=>'tasks');
  public $url='https://api.hubapi.com/';
  public $link_types=array('Contact'=>array('company'=>279,'deal'=>4), 'Company'=>array('contact'=>280),'Ticket'=>array('contact'=>16,'company'=>339),'Deal'=>array('contact'=>3,'company'=>341),'Task'=>array('contact'=>204,'company'=>192),'Note'=>array('Contact'=>202,'Company'=>190,'Deal'=>214,'Ticket'=>228),'LineItem'=>array(),'leads'=>array('contact'=>578,'company'=>610,'id'=>'0-136'),'carts'=>array('contact'=>586,'line_items'=>590),'orders'=>array('contact'=>507,'company'=>509),'invoices'=>array('contact'=>177,'company'=>179),'line_items'=>array('orders'=>514,'Deal'=>20,'carts'=>591,'invoices'=>410,'quotes'=>468),'quotes'=>array('contact'=>507,'company'=>509),'0-162'=>array('contact'=>798,'company'=>792),'0-420'=>array('contact'=>882,'company'=>884),'0-410'=>array('contact'=>860,'company'=>938),'0-421'=>array('contact'=>966,'company'=>908));
  
  function __construct($info) { 
      if(isset($info['data']) && is_array($info['data'])){
  $this->info= $info['data'];
  if(!isset($this->info['portal_id'])){
      $this->info['portal_id']='';
  }
  if(!empty($info['meta']['portal_id'])){
  // $this->info['portal_id']=$info['meta']['portal_id'];   
  }
      }
if(!empty(self::$api_timeout)){
$this->timeout=self::$api_timeout;
}

}
public function get_token(){
$info=$this->info;
if(empty($info['api_key']) && empty($info['refresh_token'])){
    return $info; //if api key or refresh token empty , do not get token
}

//$users=$this->get_users();
$ac=$this->get_account(); 
 if(!empty($ac['portalId'])){
      $info['portal_id']=$ac['portalId'];
      $info['time_zone']=$ac['timeZone'];
      $info['currency']=$ac['currency'];
      $info['valid_token'] ='true';
      unset($info['error']); 
  }else{
     unset($info['access_token']); 
     unset($info['valid_token']); 
       if(is_string($ac)){
   $info['error']=$ac;  
    }else if(isset($ac['message'])){
      $info['error']=$ac['message'];   
    }  
  }

$info['_time']=time(); 
return $info;
}

  public function refresh_token($info=""){
  if(!is_array($info)){
  $info=$this->info;
  }
  if(!isset($info['refresh_token']) || empty($info['refresh_token'])){
   return $info;   
  }
  $client=$this->client_info(); 
  ////////it is oauth    
  $body=array("client_id"=>$client['client_id'],"client_secret"=>$client['client_secret'],"redirect_uri"=>$client['call_back'],"grant_type"=>"refresh_token","refresh_token"=>$info['refresh_token']);
  $res=$this->post_hubspot('api','',$this->url.'oauth/v1/token',"post",$body);

  $re=json_decode($res,true); 
  if(isset($re['access_token']) && $re['access_token'] !=""){ 
  $info["access_token"]=$re['access_token'];
 // $info["org_id"]=$re['id'];
  $info["class"]='updated';
  $token=$info;
  }else{ 
      if(!isset($re['message'])){$re['message']='';}
  $info['error']=$re['message'];
  $info['access_token']="";
   $info["class"]='error';
  $token=array(array('errorCode'=>'406','message'=>$re['message']));

  unset($info['valid_token']); 
  }
  $info["token_time"]=time(); //api validity check
  //update hubspot info 
  //got new token , so update it in db
  $this->update_info( array("data"=> $info),$info['id']); 
  return $info; 
  }
  public function handle_code(){
      $info=$this->info;
      $id=$info['id'];
 
        $client=$this->client_info();
  $log_str=$res=""; $token=array();
  if(isset($_REQUEST['code'])){
  $code=$this->post('code'); 
  
  if(!empty($code)){
  $body=array("client_id"=>$client['client_id'],"client_secret"=>$client['client_secret'],"redirect_uri"=>$client['call_back'],"grant_type"=>"authorization_code","code"=>$code);
  $res=$this->post_hubspot('api','',$this->url.'oauth/v1/token',"post",$body);

  $log_str="Getting access token from code";
   $token=json_decode($res,true); 
   if(!isset($token['access_token'])){
      $log_str.=" =".$res; 
   }
  }
  if(isset($_REQUEST['error'])){
   $token['error_description']=$this->post('error_description');   
  }
  }else{  
  //revoke token on user request
  if(isset($info['instance_url']) && $info['instance_url']!="")
  $res=$this->post_hubspot('api','',$this->url.'/oauth/v1/refresh-tokens/'.$info['refresh_token'],'delete');  
  $log_str="Access token Revoked on Request";
  }

  $info['portal_id']='';
//var_dump($token); die();
  $info['instance_url']=$this->post('instance_url',$token);
  $info['access_token']=$this->post('access_token',$token);
  $info['client_id']=$client['client_id'];
  $info['_id']=$this->post('id',$token);
  $info['refresh_token']=$this->post('refresh_token',$token);
 // $info['issued_at']=round($this->post('issued_at',$token)/1000);
  $info['signature']=$this->post('signature',$token);
  $info['token_time']=time();
  $info['_time']=time();
  $info['error']=$this->post('message',$token);
  if(!empty($token['error'])){
  $info['error']=$this->post('error',$token);
  }
  $info['api']="api";
  $info["class"]='error';
  $info['valid_token'] ='';
  if(!empty($info['access_token'])){
  $info["class"]='updated';
  $info['valid_token'] ='true';
  $this->info=$info;
  $ac=$this->get_account();
  if(!empty($ac['portalId'])){
      $info['portal_id']=$ac['portalId'];
      $info['time_zone']=$ac['timeZone'];
      $info['currency']=$ac['currency'];
  }
  $this->info=$info;
  }
 
 // $info=$this->validate_api($info);
  $this->update_info( array('data'=> $info) , $id);
  return $info;
  }
public function get_account(){
    $url='integrations/v1/me';
return $this->post_hubspot_arr($url);
}  
  /**
  * Posts data to hubspot, Get New access token on expiration message from hubspot
  * @param  string $path hubspot path 
  * @param  string $method CURL method 
  * @param  array $body (optional) if you want to post data
  * @return array HubSpot Response array
  */
  public  function post_hubspot_arr($path,$method='get',$body=""){
  $info=$this->info;  
  $get_token=false; 
  $api=$this->post('api',$info);
  $dev_key='';
  
  if($api == 'web'){
    $dev_key=$this->post('api_key',$info);  
  }else{
    
   $token_time=(int)$this->post('token_time',$info);
   $time=time();
   $expiry=$token_time+1797;   //21600 
   if($expiry<$time){
   $info=$this->refresh_token(); 
    
   } 
      if(!empty($info['access_token'])){
  $dev_key=$info['access_token'];      
    }   
  }
  if(strpos($path,'https://') === false){
  $path=$this->url.$path;
  }
 
  $hubspot_res=$this->post_hubspot($api,$dev_key,$path,$method,$body); //var_dump($info); die();
  //var_dump($hubspot_res,$path); die();
  $hubspot_response=json_decode($hubspot_res,true); 
  if($api != 'web' && isset($hubspot_response['status']) && $hubspot_response['status'] == 'error' && isset($hubspot_response['category']) && $hubspot_response['category'] == 'EXPIRED_AUTHENTICATION'){
    $info=$this->refresh_token();  
     if(!empty($info['access_token'])){
  $dev_key=$info['access_token'];      
    }
    $hubspot_res=$this->post_hubspot($api,$dev_key,$path,$method,$body); 
  $hubspot_response=json_decode($hubspot_res,true);    
  }///var_dump($hubspot_response); die();
  if(!is_array($hubspot_response)){
      $hubspot_response=wp_strip_all_tags($hubspot_response);
  }
  if(isset($hubspot_response['status']) && $hubspot_response['status'] == 'error' && !empty($hubspot_response['message']) && strpos($hubspot_response['message'],'expired') !== false){ 
  $get_token=true;         
  }


  return $hubspot_response;   
  }
  /**
  * Posts data to hubspot
  * @param  string $dev_key Slesforce Access Token 
  * @param  string $path HubSpot Path 
  * @param  string $method CURL method 
  * @param  string $body (optional) if you want to post data 
  * @return string HubSpot Response JSON
  */
  public function post_hubspot($type,$dev_key,$path,$method,$body=""){

  $header=array(); $pars=array();
  if(is_array($body) && isset($body['grant_type'])){ //getting access token
  $header=array('content-type'=>'application/x-www-form-urlencoded');  
  $body=http_build_query($body); 
  }else{

   // if($type == 'web'){
 
  $header['Authorization']=' Bearer ' . $dev_key;     
 if($method =='file'){
     $files = array(); 
if(!empty($body['attachments_v2'])){
$files=$body['attachments_v2'];
unset($body['attachments_v2']);
$file_name='file';
} $method='post';
$boundary = md5(wp_generate_password( 24 ));
$delimiter = '-------------' . $boundary;
$header['Content-Type']='multipart/form-data; boundary='.$delimiter;
$body = $this->build_data_files($boundary, $body, $files,$file_name);
  $header['Content-Length']= strlen($body);
 // $header['User-Agent']= 'Wordpress';
  $header['Host']= 'api.hubapi.com';
//  $header['Connection']= 'keep-alive';
//  $header['Accept']= '*/*';
//  $header['Accept-Encoding']= 'gzip, deflate, br';

}else if($method != "get"){
          if(is_array($body)){
          $body=json_encode($body); 
          }
  $header['content-length']= strlen($body);
  $header['content-type']='application/json';
  }else{
      
    if(is_array($body) && count($body)>0){
    $pars=array_merge($pars,$body);    
    } 
  }
      if(count($pars)>0){
          $mark=strpos($path,'?') === false ? '?' : '&';
  $path.=$mark.http_build_query($pars);      
    }  
    }
//var_dump($header,$body); //die();
$args=array(
  'method' => strtoupper($method),
  'timeout' => $this->timeout,
  'headers' => $header,
//  'body' => $body
  );
if(!empty($body)){
    $args['body']= $body;
}
  $response = wp_remote_post( $path, $args);   //var_dump($response,$path);
 
  if(is_wp_error($response)){
      $error=$response->get_error_message();
   $body=json_encode(array('error'=>$error));   
  }else if(isset($response['body'])){
   $body=$response['body'];    
  } //
  if(empty($body)){
  $code=wp_remote_retrieve_response_code($response); 
    if($code == 204){ $body='{"code":"204","message":"No Content"}'; }
  if($code == 404){ $body='{"code":"404","message":"Not Found"}'; }
  if($code == 400){ $body='{"code":"400","message":"Bad Request"}'; }
  if(empty($body) && isset($response['response']) && is_array($response['response'])){
   $body=json_encode($response['response']);   
  }
  } //var_dump($body,$header,$path);
  return $body; 
  }
  public function build_data_files($boundary, $fields, $files, $file_name='attachments[]'){
    $data = '';
    $eol = "\r\n";

    $delimiter = '-------------' . $boundary;

    foreach ($fields as $name => $content) {
        $data .= "--" . $delimiter . $eol
            . 'Content-Disposition: form-data; name="' . $name . "\"".$eol.$eol
            . $content . $eol;
    }

    foreach ($files as $name => $file) {
    $name=basename($file);
   $content = file_get_contents($file); 
        $data .= "--" . $delimiter . $eol
            . 'Content-Disposition: form-data; name="'.$file_name.'"; filename="'.$name.'"' . $eol //
         //   . 'Content-Type: image/png'.$eol
           . 'Content-Transfer-Encoding: binary'.$eol;

        $data .= $eol;
        $data .= $content . $eol;
    }
    $data .= "--" . $delimiter . "--".$eol;


    return $data;
}
  /**
  * Get HubSpot Client Information
  * @param  array $info (optional) HubSpot Client Information Saved in Database
  * @return array HubSpot Client Information
  */
  public function client_info(){
      $info=$this->info; 
    //  if(isset($info['client_id']) && $info['client_id']=='66c8b02a-06d8-47f1-b7c9-a0e0b1b28384'){
  $client_id='66c8b02a-06d8-47f1-b7c9-a0e0b1b28384';
  $client_secret='3f9b1144-21e4-47a1-bb57-4f25be001727';
  $call_back="https://www.crmperks.com/sf_auth/";
   /*   }else{
      $client_id='9419afa6-cd5d-4fa6-959c-4b7aa93d1899';
  $client_secret='9c99496a-64c9-4c01-adf2-48db9030a838';
  $call_back="https://www.crmperks.com/sf_auth/";      
      }*/
  //custom app
  if(is_array($info)){
      if($this->post('custom_app',$info) == "yes" && $this->post('app_id',$info) !="" && $this->post('app_secret',$info) !="" && $this->post('app_url',$info) !=""){
     $client_id=$this->post('app_id',$info);     
     $client_secret=$this->post('app_secret',$info);     
     $call_back=$this->post('app_url',$info);     
      }
  }
  return array("client_id"=>$client_id,"client_secret"=>$client_secret,"call_back"=>$call_back);
  }
  public function get_forms(){
 $url='forms/v2/forms';

return $this->post_hubspot_arr($url,'get');   
   
  } 
   public function get_custom_objects(){
 $path='crm-object-schemas/v3/schemas';
$res=$this->post_hubspot_arr($path,'get'); 
//var_dump($res);
  $objects=array();
  if(!empty($res['results'])){
      foreach($res['results'] as $v){
          $objects[$v['objectTypeId']]=$v['labels']['plural'];
      }
  } 
return $objects;  
  } 
public function get_form_fields($object){
        
}  
  /**
  * Get fields from hubspot
  * @param  string $form_id Form Id
  * @param  array $form (optional) Form Settings 
  * @param  array $request (optional) custom array or $_REQUEST 
  * @return array HubSpot fields
  */
  public function get_crm_fields($object){ 

      $h_fields='["company_size","date_of_birth","degree","field_of_study","gender","graduation_date","hs_content_membership_notes","hs_content_membership_status","hs_facebook_ad_clicked","hs_facebookid","hs_google_click_id","hs_googleplusid","hs_lead_status","hs_legal_basis","hs_linkedinid","hs_twitterid","job_function","marital_status","military_status","relationship_status","school","seniority","start_date","work_email","firstname","twitterhandle","followercount","lastname","salutation","twitterprofilephoto","email","hs_persona","fax","address","hubspot_owner_id","city","linkedinbio","twitterbio","state","hs_analytics_source","zip","country","linkedinconnections","hs_language","kloutscoregeneral","jobtitle","photo","message","closedate","lifecyclestage","company","website","numemployees","annualrevenue","industry","associatedcompanyid"]'; 
      //,"mobilephone","phone","fax"
$free_fields=json_decode($h_fields,true);
if(strpos($object,'vxf_') !== false){
         $form_id=substr($object,4);
         $fields=array();
       $url='forms/v2/forms/'.$form_id;
$res=$this->post_hubspot_arr($url,'get');  
//var_dump($res); 
if(!empty($res['formFieldGroups'])){
    $form_fields=array();
    foreach($res['formFieldGroups'] as $group){
            if(!empty($group['fields'])){
        foreach($group['fields'] as $v){
      $form_fields[]=$v;
      if(!empty($v['dependentFieldFilters'])){ 
        foreach($v['dependentFieldFilters'] as $vv){
            if(!empty($vv['dependentFormField'])){
          $form_fields[]=$vv['dependentFormField'];      
            }
        }
    }
        }}
    }
    foreach($form_fields as $v){ 
        $field_arr=array('name'=>$v['name'],'label'=>$v['label'],'type'=>$v['fieldType']);
        $field_arr['req']= $v['required'] === true ? 'true' : '';
          if(!empty($v['options'])){
         $ops=$eg=array();
      foreach($v['options'] as $op){
      $ops[]=array('label'=>$op['label'],'value'=>$op['value']);
      $eg[]=$op['value'].'='.$op['label'];
      }
      if(!empty($ops)){
   $field_arr['options']=$ops;  
  $field_arr['eg']=implode(', ', array_slice($eg,0,20));
      }   
  }
  $field_arr['object']=$v['propertyObjectType'];
  if(!in_array($v['name'],$free_fields)){
      $field_arr['is_custom']='true';
  }

  $fields[$v['name']]=$field_arr;    
    
    }
 $fields['vx_consent']=array('name'=>'vx_consent','label'=>'GDPR Consent','type'=>'boolean');   

  $res_def=$this->post_hubspot_arr('email/public/v1/subscriptions','get');   
if(!empty($res_def['subscriptionDefinitions'])){
   foreach($res_def['subscriptionDefinitions'] as $v){
   $fields['vxoptin_'.$v['id']]=array('name'=>'vxoptin_'.$v['id'],'label'=>$v['name'],'type'=>'text');      
   } 
} 

 $fields['vx_url']=array('name'=>'vx_url','label'=>'Page URL','type'=>'URL');   
 $fields['vx_title']=array('name'=>'vx_title','label'=>'Page Title','type'=>'text');   
 $fields['vx_camp_id']=array('name'=>'vx_camp_id','label'=>'SFDC Campaign ID','type'=>'text');   
 $fields['vx_webinar_key']=array('name'=>'vx_webinar_key','label'=>'GoToWebinar key','type'=>'text');   
 $fields['vx_ip']=array('name'=>'vx_ip','label'=>'IP Address','type'=>'text');   
 $fields['skipValidation']=array('name'=>'skipValidation','label'=>'Skip Validation','type'=>'boolean','eg'=>'0 or 1','options'=>array(array('label'=>'True','value'=>'1'),array('label'=>'False','value'=>'0')));   
}else{
    $fields=json_encode($res);
}

//var_dump($fields,$free_fields);
return $fields;     
}

$api=$this->post('api',$this->info);      
  if($object == 'Task'){
    return array('name'=>array('name'=>'name','label'=>'Title','type'=>'Text'),'description'=>array('name'=>'description','label'=>'Note','type'=>'Text','req'=>'true'),'timestamp'=>array('name'=>'timestamp', 'label'=>'Due Date','type'=>'Datetime'));  
  }
     /*   $module='contacts'; $v='v1';  
      if($object == 'Company'){
          $module='companies'; 
      }else if($object == 'Ticket'){
          $module='tickets'; $v='v2';
      }else if($object == 'Deal'){
         $module='deals'; 
      }
      $path='properties/'.$v.'/'.$module.'/properties';*/
      $module=$object; $search_fields=$req_fields=$assoc=array();
       if(isset($this->objects[$object])){
 $module=$this->objects[$object];     
  }
  $object_id=$this->find_object_id($object);
  if(!empty($object_id) || in_array($module,array('invoices')))
  {
      $path='crm-object-schemas/v3/schemas/'.$module;
      $hubspot_response=$this->post_hubspot_arr($path);
      if(!empty($hubspot_response['requiredProperties'])){
          $req_fields=$hubspot_response['requiredProperties'];
      }
      if(!empty($hubspot_response['searchableProperties'])){
          $search_fields=$hubspot_response['searchableProperties'];
      }      if(!empty($hubspot_response['associations'])){
          $assoc=$hubspot_response['associations'];
      }
  }
      $path='crm/v3/properties/'.$module; //results
     // $path='crm-object-schemas/v3/schemas/'.$module; //properties
    //  $module='tickets';
$hubspot_response=$this->post_hubspot_arr($path);  
//var_dump($hubspot_response);
  $field_info='No Fields Found';
  if( !empty($hubspot_response['message'])){
   $field_info=$hubspot_response['message'];   
  }else if(isset($hubspot_response['results'][0]) && is_array($hubspot_response['results'][0])){
   $hub_fields=$hubspot_response['results'];   
if($api == 'web'){
$path='crm/v3/properties/'.$module.'?dataSensitivity=highly_sensitive'; //results
$hub_res=$this->post_hubspot_arr($path);  
if(isset($hub_res['results'][0])){
    $hub_fields=array_merge($hub_fields,$hub_res['results']);
} 
$path='crm/v3/properties/'.$module.'?dataSensitivity=sensitive'; //results
$hub_res=$this->post_hubspot_arr($path);  
if(isset($hub_res['results'][0])){
    $hub_fields=array_merge($hub_fields,$hub_res['results']);
}  
} //var_dump($hub_fields);
  $field_info=array();
  foreach($hub_fields as $k=>$field){ //var_dump($field); 

  if(isset($field['modificationMetadata']['readOnlyValue']) && $field['modificationMetadata']['readOnlyValue'] === false ){
  $required="";  
  if(in_array($field['name'],array('email','name','dealname','hs_lead_name','hs_course_name','hs_name'))){
  $required="true";   
  }
  if(in_array($field['name'],$req_fields)){
  $required="true";   
  } 
  if($object == 'Ticket' && in_array($field['name'],array('subject','content'))){
   $required="true";   
  }
  $type=$field['fieldType'];
  if(isset($field['type']) && in_array($field['fieldType'],array('datetime'))  ){ // 
    $type=$field['type'];  
  }
  $field_arr=array('name'=>$field['name'],"type"=>$type);
  $field_arr['label']=$field['label']; 
  $field_arr['req']=$required;
  if(!empty($field['options'])){
      $ops=$eg=array();
      foreach($field['options'] as $op){
      $ops[]=array('label'=>$op['label'],'value'=>$op['value']);
      $eg[]=$op['value'].'='.$op['label'];
      }
      if(!empty($ops)){
   $field_arr['options']=$ops;  
  $field_arr['eg']=implode(', ', array_slice($eg,0,20));
      }  
  }
  if(! (isset($field['hubspotDefined']) && $field['hubspotDefined'] == true) ){
   $field_arr['is_custom']='true';   
  }
  if(in_array($field['name'],$search_fields)){
    $field_arr['search']='1';  
  }
  $field_info[$field['name']]=$field_arr;  
  }    
  }
  if(!empty($assoc)){
      
      foreach($assoc as $v){ //
      if($object_id  == $v['fromObjectTypeId'] && $this->valid_assoc($v['name'])){
      $name=$v['toObjectTypeId'].'_vxassoc_'.$v['id'];
     $field_info[$name]=array('name'=>$name,'type'=>'lookup','label'=>'Assign '.$v['name'],'is_custom'=>'true','req'=>'');     
      } }
  }
  if(in_array($module,array('contacts','companies'))){
   $field_info['id']=array('name'=>'id','type'=>'number','label'=>'ID (Do not map this field)','is_custom'=>'true','req'=>'');    
  }
   $field_info['vx_list_files']=array('name'=>'vx_list_files',"type"=>'files','label'=>'Files - Related List','is_custom'=>'true');
  $field_info['vx_list_files2']=array('name'=>'vx_list_files2',"type"=>'files','label'=>'Files 2 - Related List','is_custom'=>'true');
  $field_info['vx_list_files3']=array('name'=>'vx_list_files3',"type"=>'files','label'=>'Files 3 - Related List','is_custom'=>'true');
  $field_info['vx_list_files4']=array('name'=>'vx_list_files4',"type"=>'files','label'=>'Files 4 - Related List','is_custom'=>'true');
  $field_info['vx_list_files5']=array('name'=>'vx_list_files5',"type"=>'files','label'=>'Files 5 - Related List','is_custom'=>'true');
  $field_info['vx_list_files6']=array('name'=>'vx_list_files6',"type"=>'files','label'=>'Files 6 - Related List','is_custom'=>'true');
  $field_info['vx_list_files7']=array('name'=>'vx_list_files7',"type"=>'files','label'=>'Files 7 - Related List','is_custom'=>'true');
  $field_info['vx_list_files8']=array('name'=>'vx_list_files8',"type"=>'files','label'=>'Files 8 - Related List','is_custom'=>'true');
  $field_info['vx_list_files9']=array('name'=>'vx_list_files9',"type"=>'files','label'=>'Files 9 - Related List','is_custom'=>'true');
  $field_info['vx_list_files10']=array('name'=>'vx_list_files10',"type"=>'files','label'=>'Files 10 - Related List','is_custom'=>'true');
  return $field_info;
  }else{
   return json_encode($hubspot_response);   
  }

  }
    
  /**
  * Get campaigns from hubspot
  * @return array HubSpot campaigns
  */
private function find_object_id($object){
$object_id=$object;
  if(isset($this->link_types[$object]['id'])){
   $object_id=$this->link_types[$object]['id'];   
  }
  if(strpos($object_id,'-')!== false)
  {
  return $object_id;    
  }
 return '';   
}
private function valid_assoc($assoc){
    $assoc=strtoupper($assoc);
    $skip=array('_ENGAGEMENT','_FORM_SUBMISSION_INBOUNDDB','_EMAIL','_COMMUNICATION','_SALES_TASK','_POSTAL_MAIL','_MEETING_EVENT','_CONVERSATION_SESSION','_SEQUENCE_ENROLLMENT','_MEETING','COMMUNICATION_');
    foreach($skip as $v){
    if(strpos($assoc,$v) !== false){
        return false;
    }    
    }
 return true;     
}
public function get_lists(){


$lists = array(); 
$offset = 0;  $more=false;
do {
$response=$this->post_hubspot_arr('crm/v3/lists/search','post',array('processingTypes'=>array('MANUAL'),'count'=>100,'offset'=>$offset));
 
  if(isset($response['lists']) && is_array($response['lists'])){
  foreach($response['lists'] as $k=>$field){
  $lists[$field['listId']]=$field['name'];    
  } 
  if (isset($response['hasMore']) && $response['hasMore'] === true) { 
      $more=true; $offset=$response['offset']; 
} else {
// No more pages, break the loop
break;
}
  }else if(isset($hubspot_response['message'])){
   return $hubspot_response['message'];   
  } else {
// Handle unexpected response format (optional)
return __('No List Found','gravity-forms-hubspot-crm');; // Or throw an exception
}
} while ($more); // Loop until there's no more "next" link

  return $lists;
}
public function get_pipes($object='tickets'){ 
$hubspot_response=$this->post_hubspot_arr('crm/v3/pipelines/'.$object); //crm-pipelines/v1/pipelines/
//var_dump($hubspot_response); die();
  ///seprating fields
  $field_info=__('No List Found','gravity-forms-hubspot-crm');
  if(isset($hubspot_response['results']) && is_array($hubspot_response['results'])){
  $field_info=array();
  foreach($hubspot_response['results'] as $k=>$field){
      if(!empty($field['stages']) && $field['archived'] === false ){ //v1 api uses $field['active'] === true  not supports archived field
if(!empty($field['stages'])){
foreach($field['stages'] as $stage){
  if(isset($stage['id'])){
  $field_info[$field['id'].'-v_xx-'.$stage['id']]=$field['label'].' - '.$stage['label'];
      }   
}
}  }
  }
  }
    if(isset($hubspot_response['message'])){
   $field_info=$hubspot_response['message'];   
  }
  return $field_info;
}
public function get_flows(){ 
  $hubspot_response=$this->post_hubspot_arr('automation/v3/workflows');  
 // var_dump($hubspot_response); die();
  ///seprating fields
  $field_info=__('No Work Flow Found','gravity-forms-hubspot-crm');
  if(isset($hubspot_response['workflows']) && is_array($hubspot_response['workflows'])){
  $field_info=array();
  foreach($hubspot_response['workflows'] as $k=>$field){
      if($field['enabled'] === true){
  $field_info[$field['id']]=$field['name'];}     
  }
  }
    if(isset($hubspot_response['message'])){
   $field_info=$hubspot_response['message'];   
  }
  return $field_info;
}
  /**
  * Get users from hubspot
  * @return array HubSpot users
  */
public function get_users(){ 

  $hubspot_response=$this->post_hubspot_arr('crm/v3/owners');

  ///seprating fields
  $field_info=__('No Users Found');
    if( !empty($hubspot_response['message'])){
   $field_info=$hubspot_response['message'];   
  } else if( isset($hubspot_response['results']) && is_array($hubspot_response['results'])){
  $field_info=array();
  foreach($hubspot_response['results'] as $k=>$field){
  $field_info[$field['id']]=$field['firstName'].' '.$field['lastName'].' ( '.$field['email'].' )';     
  }
  }

  return $field_info;
}
  /**
  * Posts object to hubspot, Creates/Updates Object or add to object feed
  * @param  array $entry_id Needed to update hubspot response
  * @return array HubSpot Response and Object URL
  */
public function push_object($object,$fields,$meta){ 

/* $sbody=array('filterGroups'=>array(array('filters'=>array(array('propertyName'=>'hs_order_name',"operator"=> "EQ",'value'=>'woo- 18052')) )),'properties'=>array('hs_order_name')); //,'description'
 $spath='crm/v3/objects/orders/search'; 
 $search_res=$this->post_hubspot_arr($spath,'post',$sbody);
 var_dump($search_res); die();
$path='crm-object-schemas/v3/schemas/leads'; //custom objects //searchableProperties
//$path='crm/v3/properties/contacts';
//$path='crm/v3/properties/orders';
//$path='crm/v3/properties/services';
//$path='crm/v3/properties/0-410';
//$path='crm/v3/properties/0-421';
//$path='crm/v3/properties/listings';
//$path='crm/v3/objects/0-420';  //listings
//$path='crm/v3/objects/0-421';  //appointemts
//$path='crm/v3/objects/0-162'; //services
//$path='crm/v3/objects/0-410';  //courses
//$path='crm/v3/objects/leads';
//$path='crm/v3/objects/drivers';
//$path='crm/v4/associations/2-39662400/2-41674652/labels'; //deal/labels
//$path='crm/v3/objects/0-410';
//$path='crm/v3/objects/'.$object;
//$path='crm/v3/objects/orders/405243209390';
//$path='crm/v3/object-library/enablement';
$res=$this->post_hubspot_arr($path,'get');
//$res=$this->get_pipes('services');
var_dump($res); die();
$arr='{"hs_lead_name":"touseefcccdd ahmadhcccsdd","hs_lead_type":"NEW_BUSINESS","hs_lead_label":"WARM","hs_pipeline":"","hs_pipeline_stage":""}';
//cart
//  $post_data='{"properties":{"email":"test1@localhost.com","name":"touseefcccdd ahmadhcccsdd","city":"houston","car_type":"sportage"},"associations":[{"to":"25054424432","types":[{"associationCategory":"HUBSPOT_DEFINED","associationTypeId":"53"}]}]}';
$arr='{
    "price": 10,
    "quantity": 1,
    "name": "New standalone line item",
    "hs_sku": "test-sku",
    "description": "the line item description is eher "
  }';  
$links=array();
//$links[]=array("to"=>array('id'=>'405243209390'),'types'=>array(array("associationCategory"=> "HUBSPOT_DEFINED","associationTypeId"=>$this->link_types[$object]['Order'])));
//$links[]=array("to"=>array('id'=>'100040290088'),'types'=>array(array("associationCategory"=> "HUBSPOT_DEFINED","associationTypeId"=>860)));
 //var_dump($this->link_types['0-410']['contact']); die(); 
//$post=array('properties'=>json_decode($arr,1),'associations'=>$links);
//$post_data=json_encode($post);
//var_dump($post_data,$object); die('--------');
//$hubspot_response=$this->post_hubspot_arr($path,'post',$post_data);
//var_dump($hubspot_response,$post_data); die();*/
   $extra=$contact=array();
  $portal_id=$id=""; $error=""; $action=""; $link=""; $search=$search_response=$status=""; 
  // entry note
  $entry_exists=false;
    $debug = isset($_REQUEST['vx_debug']) && current_user_can('manage_options'); 
    $event=$this->post('event',$meta);
  $sobject=$object;
  if(isset($this->objects[$object])){
 $sobject=$this->objects[$object];     
  }
  $object_id=$this->find_object_id($object);
      //remove related list fields
  $files=array();
  for($i=1; $i<11; $i++){
$field_n='vx_list_files';
if($i>1){ $field_n.=$i; }
  if(isset($fields[$field_n]['value'])){
    $files=$this->verify_files($fields[$field_n]['value'],$files);
    unset($fields[$field_n]);  
  }
}
   
    if(isset($fields['id'])){
      $id=$fields['id']['value']; unset($meta['primary_key']); unset($fields['id']);
  }
  if($debug){ ob_start();}
  //check primary key
  if(isset($meta['primary_key']) && $meta['primary_key']!="" && isset($fields[$meta['primary_key']]['value']) && $fields[$meta['primary_key']]['value']!=""){    
  $search=$fields[$meta['primary_key']]['value'];
  $field=$meta['primary_key'];
    if($object == 'Contact' && $field == 'email'){
 $spath='contacts/v1/contact/email/'.$search.'/profile';
$search_response=$this->post_hubspot_arr($spath);
if(!empty($search_response['vid'])){
 $id=$search_response['vid'];   
$contact =$search_response['properties'];   
}

}else{ //if(in_array($object, array('Ticket','Deal','Company')))

 $spath='crm/v3/objects/'.$sobject.'/search';
 $sbody=array('filterGroups'=>array(array('filters'=>array(array('propertyName'=>$field,"operator"=> "EQ",'value'=>$search)))));
 $search_response=$hubspot_response=$this->post_hubspot_arr($spath,'post',$sbody); 
 if(!empty($search_response['results'][0]['id'])){
   $search_response=$search_response['results'][0];
   $id=$search_response['id']; 
 }
 //var_dump($search_response); die();
 }/*else{

  $sbody=array();
  $spath='contacts/v1/search/query';
  if($object == 'Company'){
  $spath='companies/v2/companies/'.$field.'/'.str_replace(array('http://','https://'),'',$search);    
  }else{
  $sbody['q']=$search;    
  }
  //search object
  //if primary key option is not empty and primary key field value is not empty , then check search object
  $search_response=$hubspot_response=$this->post_hubspot_arr($spath,'get',$sbody); 
 // var_dump($search_response,$spath,$sbody); die();

        if($search !=""){
      if(is_array($search_response) && count($search_response)>10){
       $search_response=array_slice($search_response,count($search_response)-10,10);   
      }
  }
  if(isset($hubspot_response[0]['Id'])&& $hubspot_response[0]['Id']!=""){
  //object found, update old object or add to feed
  $id=$hubspot_response[count($hubspot_response)-1]['Id'];
      $entry_exists=true;
  }
  if(!empty($hubspot_response['message'])){
  $error=$hubspot_response['message'];
  }else{
       if($object == 'Company' && isset($hubspot_response[0]['companyId']) ){
           $id=$hubspot_response[0]['companyId'];
           $portal_id=$hubspot_response[0]['portalId'];
       }else  if($object == 'Contact' && isset($hubspot_response['contacts'][0]['vid'])){
      $id=$hubspot_response['contacts'][0]['vid'];     
      $portal_id=$hubspot_response['contacts'][0]['portal-id'];     
       }
  }
    }*/
     $extra["body"]=$search;
      $extra["response"]=$search_response;   
  if($debug){
  ?>
  <pre>
  <h3>Search field</h3>
  <p><?php print_r($field) ?></p>
  <h3>Search term</h3>
  <p><?php print_r($search) ?></p>
  <h3>Search response</h3>
  <p><?php print_r($hubspot_response) ?></p>
  </pre>    
  <?php
  }

  $hubspot_response='';
  }

  if(!empty($meta['crm_id'])){
   $id=$meta['crm_id'];   
  } 

  $note_object='';
     if(in_array($event,array('delete_note','add_note'))){    
  if(isset($meta['related_object'])){
      $note_object=$meta['related_object'];
    $extra['Note Object']= $meta['related_object'];
  }
  if(isset($meta['note_object_link'])){
    $extra['note_object_link']=$meta['note_object_link'];
  }
}

if(!empty($meta['add_pipe']) && !empty($meta['pipe']) ){
      $sep=strpos($meta['pipe'],'-v_xx-') !== false ? '-v_xx-' : '-';
    $exp=explode($sep,$meta['pipe']);
    $fields['hs_pipeline']=array('value'=>$exp[0],'label'=>'Pipeline');
    if(!isset($fields['hs_pipeline_stage'])){
    $fields['hs_pipeline_stage']=array('value'=>$exp[1],'label'=>'Pipeline Stage');
    }
}
if(!empty($meta['add_sales_pipe']) && !empty($meta['sales_pipe']) ){
    $sep=strpos($meta['sales_pipe'],'-v_xx-') !== false ? '-v_xx-' : '-';
    $exp=explode($sep,$meta['sales_pipe']);
    $pipeline_name='hs_pipeline'; $stage_name='hs_pipeline_stage';
    if($object == 'Deal'){ $pipeline_name='pipeline'; $stage_name='dealstage'; }
    $fields[$pipeline_name]=array('value'=>$exp[0],'label'=>'Pipeline');
    //if(!isset($fields['dealstage'])){
    $fields[$stage_name]=array('value'=>$exp[1],'label'=>'Stage');
   // }
}
 if(!empty($meta['OwnerId']['value'])){
$fields['hubspot_owner_id']=array('value'=>$meta['OwnerId']['value'],'label'=>'Owner');  
 }
//var_dump($fields); 
  $path='crm/v3/objects/'; $send_body=false; $post=$links=$field_links=array(); //$meta['_vx_contact_id']=array('value' =>301);
  if(isset($this->objects[$object])){
 $path.=$this->objects[$object];     
  }else{
 $path.=$object;    
  }
  //if($error ==""){
  if($id == ""){
  $action="Added";
 $send_body=true;
  
   if($object == 'Task'){ 
   $send_body=false;
   
if(!empty($fields['description']['value'])){
    $post['hs_task_body']=$fields['description']['value'];
     if(!empty($fields['name']['value'])){
   $post['hs_task_subject']=$fields['name']['value'];    
   }
   if(!empty($meta['OwnerId']['value'])){    
 $post['hubspot_owner_id']=$meta['OwnerId']['value'];    
 }
    if(!empty($fields['timestamp']['value'])){
       $t=strtotime($fields['timestamp']['value']);
       if(!empty($t)){
       $offset=get_option('gmt_offset') * 3600;
        $t-=$offset;
        $t=$t.'000';
       }
   $post['hs_timestamp']=$t;
   }

   }
//var_dump($post,$fields); die();   
  }    
  $method='post';
$status="1";

  }
  else{ 

  if($event == 'add_note'){  
  $note_post=$this->note_post(array('Body'=>$fields['Body']['value']),$id,$note_object);  
  $post=$note_post['properties'];    
  $links=$note_post['associations'];    
  $status="1";
  $method='post';  
  }
  else if(in_array($event,array('delete','delete_note'))){
     $method='delete';  
  $action="Deleted";
$path.='/'.$id;
    $status="5";  
  }
  else{    
      
  $action="Updated"; $method='patch';
  $path.='/'.$id;

  $status="2"; $send_body=true;
   if(!empty($meta['update'])){ $path=''; }
  }

  }

$is_form=false;
if(strpos($object,'vxf_') !== false){
$is_form=true;
 //,'sfdcCampaignId'=>'','goToWebinarWebinarKey'=>''
/*$post=array(
'context'=>array('pageUri'=>'https://local','pageName'=>'Local Test','hutk'=>'25886241a10e48ad86842f0125b578f7','ipAddress'=>'120.23.56.58'),
'legalConsentOptions'=>array('consent'=>array(
'consentToProcess'=>true,"text"=>'I agree to allow Example Company to store and process my personal data.',
//'communications'=>array(array('value'=>true,'text'=>'Text','subscriptionTypeId'=>'5388809'))
))
);*/

$form_id=substr($object,4);

if(!empty($this->info['portal_id']) && !empty($form_id) && !empty($fields) ){
$path='https://api.hsforms.com/submissions/v3/integration/submit/'.$this->info['portal_id'].'/'.$form_id;
if(!isset($fields['vx_url']) && !empty($meta['_vx_entry']['_vx_url'])){
    $fields['vx_url']=array('value'=>$meta['_vx_entry']['_vx_url'] , 'label'=>'Page URL');
}
if(!isset($fields['vx_title'])){
    $title='';
    if( !empty($meta['_vx_entry']['_vx_title'])){ $title=$meta['_vx_entry']['_vx_title']; }
    else if( !empty($meta['_vx_entry']['_vx_form_name'])){ $title=$meta['_vx_entry']['_vx_form_name']; }
    if(!empty($title)){
    $fields['vx_title']=array('value'=>$title , 'label'=>'Form Name');
    }
}
if(!isset($fields['vx_ip']) && !empty($meta['_vx_entry']['_vx_ip']) && filter_var($meta['_vx_entry']['_vx_ip'], FILTER_VALIDATE_IP)){
    $fields['vx_ip']=array('value'=>$meta['_vx_entry']['_vx_ip'] , 'label'=>'IP Address');
}


$context=array('vx_url'=>'pageUri','vx_title'=>'pageName','vx_ip'=>'ipAddress','vx_camp_id'=>'sfdcCampaignId','vx_webinar_key'=>'goToWebinarWebinarKey');
$com_consent=array();
foreach($fields as $k=>$v){
if(isset($context[$k])){
if($k == 'vx_url' && !empty($v['value'])){
    $v['value'] = strtok($v['value'], '?');
}
$post['context'][$context[$k]]=$v['value'];     
}else if($k == 'skipValidation'){
   $post['skipValidation']=empty($v['value']) ? false : true;    
}else if($k == 'vx_consent'){
$post['legalConsentOptions']=array('consent'=>array(
'consentToProcess'=>!empty($v['value']),
"text"=>'Yes, you can store and process my personal data.',
//"communications"=>array(array('value'=>true,'subscriptionTypeId'=>10577111,'text'=>'Yes, subscribe me'))
)); 
  
}else if(strpos($k,'vxoptin_') === 0){
    $val=!empty($v['value']) ? true : false;
 $com_consent[]=array('value'=>$val,'text'=>'Yes','subscriptionTypeId'=>substr($k,8));   
}else{
$type = !empty($meta['fields'][$k]['type']) ? $meta['fields'][$k]['type'] : '';    
$obj_type = !empty($meta['fields'][$k]['object']) ? $meta['fields'][$k]['object'].'.' : '';  
if( !in_array($obj_type,array('zTICKET.','zCOMPANY.')) ){ $obj_type=''; } //company name field in contact needs COMPANY.name   
$v['value']=$this->verify_val($v['value'],$type);
$post['fields'][]=array('name'=>$obj_type.$k,'value'=>$v['value'],'objectTypeId'=>!empty($meta['fields'][$k]['object']) ? $meta['fields'][$k]['object']: null);
} }

if(!empty($meta['_vx_entry']['_vx_htuk'])){
    $post['context']['hutk']=$meta['_vx_entry']['_vx_htuk'];
$fields['hutk']=array('value'=>$meta['_vx_entry']['_vx_htuk'] , 'label'=>'Cookie');
}

if(!empty($com_consent) && isset($post['legalConsentOptions'])){
   $post['legalConsentOptions']['consent']['communications']= $com_consent;
}
//var_dump($post,$fields,$meta['fields']); die();
}
     
}
else{
if($send_body){     
$key='property';
if( in_array($object,array('Company','Ticket','Deal') ) ){
$key='name';
}

if(is_array($fields)){
foreach($fields as $k=>$v){
$type = !empty($meta['fields'][$k]['type']) ? $meta['fields'][$k]['type'] : '';  
if(strpos($k,'_vxassoc_')!== false){
    $ass_arr=explode('_vxassoc_',$k);
$field_links[]=array('to'=>$v['value'],'from'=>$id,'to_object'=>$ass_arr[0],'from_object'=>$object_id,"type"=>$ass_arr[1]);
continue;
}  
$v['value']=$this->verify_val($v['value'],$type);
if($type == 'checkbox' && !empty($meta['fields'][$k]['is_custom']) && !empty($contact[$k]['value'])){
  $v['value']=$contact[$k]['value'].';'.$v['value']; 
  $v['value']=array_unique(explode(';',$v['value']));
  $v['value']=implode(';',$v['value']); 
}
$post[$k]=$v['value'];    
} //var_dump($post); die();
} }

 if($status == '1'){ //associations work for new record only , for old records use put method at end 
if(!empty($meta['_vx_contact_id']['value']) && isset($this->link_types[$object]['contact'])){
$links[]=array("to"=>array('id'=>$meta['_vx_contact_id']['value']),'types'=>array(array("associationCategory"=> "HUBSPOT_DEFINED","associationTypeId"=>$this->link_types[$object]['contact'])));
$extra['Assign Contact']=$meta['_vx_contact_id']['value'];  
}
      if(!empty($meta['_vx_company_id']['value']) && isset($this->link_types[$object]['company'])){
  $links[]=array("to"=>array('id'=>$meta['_vx_company_id']['value']),'types'=>array(array("associationCategory"=> "HUBSPOT_DEFINED","associationTypeId"=>$this->link_types[$object]['company'])));
   $extra['Assign Company']=$meta['_vx_company_id']['value']; 
   }
 foreach($field_links as $k=>$v){
     $type=isset($this->link_types[$object]) ? 'HUBSPOT_DEFINED' : 'USER_DEFINED' ;
   $extra['Assign '.$k]=$links[]=array("to"=>(string)$v['to'],'types'=>array(array("associationCategory"=> $type,"associationTypeId"=>$v['type'])));  
 }  
}
$post=array('properties'=>$post,'associations'=>$links);
if($object == 'Task'){
  //var_dump($post); die();  
}
//}
} 
$hubspot_response=array(); //var_dump($path,$post); die('-----------');
if(!empty($path)){
 $post_data=json_encode($post);
//var_dump($path,$post,$object,$id); die('--------');
$hubspot_response=$this->post_hubspot_arr($path,$method,$post_data);
//var_dump($hubspot_response,$post,$path); die();
if($object == 'Task'){
//var_dump($post,$hubspot_response); die();
}
}
if(!$is_form){
    $id_key='id';
    if($object == 'Deal'){
      //  $id_key='dealId';
    }

  if(isset($hubspot_response[$id_key])){
  $id=$hubspot_response[$id_key];

if(!empty($meta['order_items']) && $status == '1'){
  $items=$this->get_wc_items($meta);
  $assoc_items=$filter_ids=array(); $filters=array();
     if(!empty($meta['order_products'])){
      $hub_name='name'; $woo_name='title';
      if( !empty($meta['search_products']) ){
      $hub_name='hs_sku';    
      $woo_name='sku';    
      }    
  foreach($items as $k=>$item){
      $filter_ids[$k]=$item[$woo_name];
 $filters[]=array('filters'=>array(array('propertyName'=>$hub_name,"operator"=> "EQ",'value'=>$item[$woo_name])) );
      } 
 $sbody=array('filterGroups'=>$filters,'properties'=>array('name','hs_sku','price')); //,'description'
 $spath='crm/v3/objects/products/search'; 
 $search_res=$this->post_hubspot_arr($spath,'post',$sbody);
  
 if(!empty($search_res['results'])){
     $extra['products search']=array_slice($search_res['results'],0,60); 
  foreach($search_res['results'] as $v){
      if(!empty($v['properties'][$hub_name])){
       $item_pos=array_search($v['properties'][$hub_name],$filter_ids);
       if($item_pos !== false){
        $items[$item_pos]['hub_id']=$v['id'];   
       }   
      }
  }   
 }else{
     $extra['products search']=$search_res; 
 }
     }
       foreach($items as $k=>$item){
          $item_prop=array('name'=>$item['title'],'price'=>$item['unit_price'],'quantity'=>$item['qty']); 
           if(!empty($meta['order_products'])){
               if(!empty($item['hub_id'])){
             $item_prop['hs_product_id']=$item['hub_id'];      
               }else{
         $product_post=array('name'=>$item['title'],'price'=>$item['unit_price'],'description'=>esc_html($item['desc']));  
  $path='crm/v3/objects/products';
 $product_res=$this->post_hubspot_arr($path,'post',json_encode(array('properties'=>$product_post)));
 $extra['create product']=$product_res;    
  if(!empty($product_res['id'])){
      $item_prop['hs_product_id']=$product_res['id'];
  }
           }
  }
  $assoc_items[]=array('properties'=>$item_prop,'associations'=>array(array("to"=>array('id'=>$id),'types'=>array(array("associationCategory"=> "HUBSPOT_DEFINED","associationTypeId"=>$this->link_types['line_items'][$object] )))) );    
       }

 //create line item
   $path='crm/v3/objects/line_items/batch/create'; 
   $extra['line items']=$assoc_items; 
    $product_res=$this->post_hubspot_arr($path,'post',json_encode(array('inputs'=>$assoc_items))); 
    $extra['Add line items']=$product_res;      

}
 // $status="1";
  }else if(isset($hubspot_response['objectId'])){
  $id=$hubspot_response['objectId'];
  $portal_id=$hubspot_response['portalId'];
   $link='https://app.hubspot.com/contacts/'.$portal_id.'/ticket/'.$id.'/'; 
  $status="1";
  }else{
  //$status="";  //disabled it because,  shows unknow error for do not update contact  
  }


}else{
 $status="1";   
}
  //
  if(isset($hubspot_response['message'])){
  $error=$hubspot_response['message'];
  if(!empty($hubspot_response['errors'][0]['message'])){
        $error=$hubspot_response['errors'][0]['message'];
    }
  $id=''; $status=''; 
  }else{
      $portal_id=$this->info['portal_id'];
      if(isset($hubspot_response['vid']) && isset($hubspot_response['portal-id'])){
        $id=$hubspot_response['vid'];
        $portal_id=$hubspot_response['portal-id'];
        
      }else if(isset($hubspot_response['companyId']) && isset($hubspot_response['portalId'])){
       $id=$hubspot_response['companyId'];
        $portal_id=$hubspot_response['portalId'];
      }
      if(isset($hubspot_response['engagement']['id'])){
       $id=$hubspot_response['engagement']['id'];   
      }
      if(!empty($id) && !empty($portal_id)){
   if($object == 'Company'){
     $link='https://app.hubspot.com/sales/'.$portal_id.'/company/'.$id.'/';     
   }else if($object == 'Deal'){
     $link='https://app.hubspot.com/sales/'.$portal_id.'/deal/'.$id.'/';     
   }else if($object == 'leads'){
     $link='https://app.hubspot.com/prospecting/'.$portal_id.'/leads/view/all-leads?leadId='.$id;     
   }else if($object == 'orders'){
     $link='https://app.hubspot.com/contacts/'.$portal_id.'/record/0-123/'.$id;     
   }else if(strpos($object,'-')!== false){
     $link='https://app.hubspot.com/contacts/'.$portal_id.'/record/'.$object.'/'.$id;     
   }else if($object == 'Contact'){   
   $link='https://app.hubspot.com/contacts/'.$portal_id.'/contact/'.$id.'/';  
   //add to list and work flow
   if(!empty($fields['email']['value']) && !empty($meta['add_flow']) && !empty($meta['flow'])){
       $email=$fields['email']['value'];
    $path='automation/v2/workflows/'.$meta['flow'].'/enrollments/contacts/'.$email ;
   $work_res=$this->post_hubspot_arr($path,'post');
   $extra['Enrol to Work Flow']=$meta['flow'];   
   $extra['Work Flow Response']=$work_res;   
   }
   //add to static list
      if( !empty($meta['add_list']) && !empty($meta['list'])){
   $path='crm/v3/lists/'.$meta['list'].'/memberships/add' ;
   $list_res=$this->post_hubspot_arr($path,'put',array($id));
   $extra['Add to List']=$meta['list']; 
   $extra['List Rsponse']=$list_res; 
       
      }

   
   }
 if($status == '2'){
     if(!empty($meta['_vx_contact_id']['value']) && isset($this->link_types[$object]['contact'])  ){
      $extra['Assign Contact path']=$path='crm/v3/objects/'.$sobject.'/'.$id.'/associations/contacts/'.$meta['_vx_contact_id']['value'].'/'.$this->link_types[$object]['contact'];
       $comp_res=$this->post_hubspot_arr($path,'put'); 
   $extra['Assign Contact']=$comp_res;  
   }
      if(!empty($meta['_vx_company_id']['value']) && isset($this->link_types[$object]['company']) ){
$extra['Assign Company path']=$path='crm/v3/objects/'.$sobject.'/'.$id.'/associations/companies/'.$meta['_vx_company_id']['value'].'/'.$this->link_types[$object]['company'];
        $comp_res=$this->post_hubspot_arr($path,'put');
   $extra['Assign Company']=$comp_res; 
   }
    foreach($field_links as $k=>$v){ 
   $extra['Assign path '.$k]=$path='crm/v3/objects/'.$v['from_object'].'/'.$v['from'].'/associations/'.$v['to_object'].'/'.$v['to'].'/'.$v['type'];
        $comp_res=$this->post_hubspot_arr($path,'put');
   $extra['Assign '.$k]=$comp_res; 
 }   
 }
 $file_ids=array();
       if(is_array($files) ){
        foreach($files as $k=>$file){ $k++;
        $filer=rtrim($file,'/'); 
         $file_name=substr($filer,strrpos($filer,'/')+1);  
            if(strpos($file,'/pdf/') !== false){
    $file_name.='.pdf';    
    }     
         if( filter_var($file, FILTER_VALIDATE_URL) && strpos($file,'/gravity_forms/') !== false) { //!ini_get('allow_url_fopen')
      $upload_dir=wp_upload_dir();
       $file=str_replace($upload_dir['baseurl'],$upload_dir['basedir'],$file); 
    } $path='files/v3/files';
     $post_data=array('folderPath'=>'/','attachments_v2'=>array($file),'options'=>'{"access":"PRIVATE"}');
    $extra['Upload file '.$k]=$file_arr=$this->post_hubspot_arr($path,'file',$post_data); //var_dump($file_arr,$post_data); die();
     if(!empty($file_arr['id'])){
         $file_ids[]=$file_arr['id'];    
     }
        }}
    if(!empty($file_ids)){
        $path='crm/v3/objects/notes';
    $post_data=array('hs_attachment_ids'=>implode(',',$file_ids),'hs_timestamp'=>date('c'),'hs_note_body'=>'');
    $post_data=array('properties'=>$post_data, 'associations'=>array(array("to"=>array('id'=>$id),'types'=>array(array("associationCategory"=> "HUBSPOT_DEFINED","associationTypeId"=>$this->link_types['Note'][$object] )))) );
    $extra['Attach Files']=$this->post_hubspot_arr($path,'post',$post_data);
    } 

   //
      }
      //
  }


  if($debug){
  ?>
  <pre>
  <h3>HubSpot Information</h3>
  <p><?php print_r($this->info) ?></p>
  <h3>Data Sent</h3>
  <p><?php echo json_encode($fields) ?></p>
  <h3>HubSpot response</h3>
  <p><?php print_r($hubspot_response) ?></p>
  <h3>Object</h3>
  <p><?php print_r($object."--------".$action) ?></p>
  </pre>    
  <?php
  $contents=trim(ob_get_clean());
  if($contents!=""){
  update_option($this->id."_debug",$contents);   
  }
  }
   //add entry note
 if(!empty($meta['__vx_entry_note']) && !empty($id)){
 $disable_note=$this->post('disable_entry_note',$meta);
   if(!($entry_exists && !empty($disable_note))){
       $entry_note=$meta['__vx_entry_note'];
       $path='crm/v3/objects/notes';
       $note_post=$this->note_post($entry_note,$id,$object);
  $extra['Note Response']=$this->post_hubspot_arr($path,"POST",$note_post); 
  $extra['Note Body']=$entry_note['Body'];  
   }  
 }

  return array("error"=>$error,"id"=>$id,"link"=>$link,"action"=>$action,"status"=>$status,"data"=>$fields,"response"=>$hubspot_response,"extra"=>$extra);
}
public function note_post($entry_note,$id,$object){
              
     $note_post=array('properties'=>array('hs_note_body'=>$entry_note['Body'],'hs_timestamp'=>date('c')),'associations'=>array());
          
          if( isset($this->link_types['Note'][$object])){
$note_post['associations'][]=array("to"=>array('id'=>$id),'types'=>array(array("associationCategory"=> "HUBSPOT_DEFINED","associationTypeId"=>$this->link_types['Note'][$object])));  
}
return $note_post;
}
public function verify_val($val,$type){
   // $type=isset($field['type']) ? $field['type'] : '';
            if( $type == 'file'  && is_string($val)){
            $files_temp=json_decode($val,true);
            if(!empty($files_temp)){
             $val=$files_temp;   
            }
    
        }
        if(is_array($val)){
        $val=implode(';',$val);     
        }
        if($type == 'number'){
        $val=filter_var( $val, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION ); 
        }
        if($type == 'booleancheckbox'){
            $val=!empty($val) ? 'true' : 'false';
        }
        if($type == 'date'){
            try{
        $date = new DateTime( $val );
        $date->modify( 'midnight' );
     $val=$date->getTimestamp() * 1000;
            }catch(Exception $e){
                
            }
        }
      if(in_array($type,array('datetime'))){
      $val=strtotime($val).'000';      
      }
return html_entity_decode($val);      
}
public function verify_files($files,$old=array()){
        if(!is_array($files)){
        $files_temp=json_decode($files,true);
     if(is_array($files_temp)){
    $files=$files_temp;     
     }else if(!empty($files)){ //&& filter_var($files,FILTER_VALIDATE_URL)
      $files=array_map('trim',explode(',',$files));   
     }else{
      $files=array();    
     }   
    }
    if(is_array($files) && is_array($old) && !empty($old)){
   $files=array_merge($old,$files);     
    }
  return $files;  
}
public function get_wc_items($meta){
      $_order=self::$_order;
    //  $fees=$_order->get_shipping_total();
    //  $fees=$_order-> get_total_discount();
    //  $fees=$_order-> get_total_tax();

     $items=$_order->get_items(); 
     $products=array();  $order_items=array(); 
if(is_array($items) && count($items)>0 ){
foreach($items as $item_id=>$item){

$sku=$desc=''; $qty=$unit_price=$tax=$total=$p_id=0;
if(method_exists($item,'get_product')){
  // $p_id=$v->get_product_id();  
   $product=$item->get_product();
   if(!$product){ continue; } //product deleted but exists in line items of old order

   $total=(int)$item->get_total();
   $qty = $item->get_quantity();
   $tax = $item->get_total_tax();

   $desc=$product->get_short_description();
   $title=$product->get_title();
   $sku=$product->get_sku();     
   $unit_price=$product->get_price(); 
   $p_id=$product->get_parent_id();
   if(empty($p_id)){
   $p_id=$product->get_id();
   }
   if(empty($total)){ $unit_price=0; } 
   
      if(method_exists($_order,'get_item_total')){
      // $cost=(float)$_order->get_item_total($item,false,true); //including woo coupon discuont
       $unit_price=(float)$_order->get_item_subtotal($item, false, true); // does not include coupon discounts
   
     if(!empty($meta['item_price_custom'])){
    //  $cost=(float)wc_get_order_item_meta($item->get_id(),$meta['item_price_custom'],true); 
     }   
      // $cost=round($cost,2);
       $unit_price=round($unit_price,2);
    }
          
   }else{ //version_compare( WC_VERSION, '3.0.0', '<' )  , is_array($item) both work
          $line_item=$this->wc_get_data_from_item($item); 
   $p_id= !empty($line_item['variation_id']) ? $line_item['variation_id'] : $line_item['product_id'];
        $line_desc=array();
        if(!isset($products[$p_id])){
        $product=new WC_Product($p_id);
        }else{
         $product=$products[$p_id];   
        }
       if(!$product){ continue; }  
        $qty=$line_item['qty'];
        $products[$p_id]=$product;
        $sku=$product->get_sku(); 
        if(empty($sku) && !empty($line_item['product_id'])){ 
            //if variable product is empty , get simple product sku
            $product_simple=new WC_Product($line_item['product_id']);
            $sku=$product_simple->get_sku(); 
        }
        $unit_price=$product->get_price();
        $title=$product->get_title();
        $desc=$product->get_short_description();
        $p_id=$line_item['product_id'];
          }
  $temp=array('sku'=>$sku,'unit_price'=>$unit_price,'title'=>$title,'qty'=>$qty,'tax'=>$tax,'total'=>$total,'desc'=>$desc,'p_id'=>$p_id);
          if(method_exists($product,'get_stock_quantity')){
   $temp['stock']=$product->get_stock_quantity();
} 
     $order_items[]=$temp;     
      }
} 
     
   return $order_items;       
}

public function get_entry($object,$id){
        $path='contacts/v1/contact/vid/'.$id.'/profile';
        if($object == 'Company'){
        $path='/companies/v2/companies/'.$id;    
        }else if($object == 'Task'){
        $path='/engagements/v1/engagements/'.$id;    
        }
  $arr=$this->post_hubspot_arr($path);
  $entry=array();  // var_dump(isset($arr['metadata']));
  if(isset($arr['metadata'])){
     $arr['properties']=$arr['metadata']; 
  }
  if(isset($arr['properties']) && is_array($arr['properties']) && count($arr['properties'])>0){
      foreach($arr['properties'] as $k=>$v){
          
          if(isset($v['value']) && !is_array($v['value'])){
           $entry[$k]=$v['value']; 
          }else if(!is_array($v)){
          $entry[$k]=$v;    
          }
 
      }
  }
 // var_dump($arr,$path,$entry); die();
  return $entry;     
  }
public function create_fields_section($fields){
$arr=array(); 
if(!isset($fields['object'])){
    $objects=array(''=>'Select Object','Contact'=>'Contact','Company'=>'Company');
    if(is_array($objects_sf)){
    $objects=array_merge($objects,$objects_sf);
    }
 $arr['gen_sel']['object']=array('label'=>__('Select Object','gravity-forms-hubspot-crm'),'options'=>$objects,'is_ajax'=>true,'req'=>true);   
}else if(isset($fields['fields']) && !empty($fields['object'])){
    // filter fields
    $crm_fields=$this->get_crm_fields($fields['object']); 
    if(!is_array($crm_fields)){
        $crm_fields=array();
    }
    $add_fields=array();
    if(is_array($fields['fields']) && count($fields['fields'])>0){
        foreach($fields['fields'] as $k=>$v){
           $found=false;
                foreach($crm_fields as $crm_key=>$val){
                    if(strpos($crm_key,$k)!== false){
                        $found=true; break;
                }
            }
         //   echo $found.'---------'.$k.'============'.$crm_key.'<hr>';
         if(!$found){
       $add_fields[$k]=$v;      
         }   
        }
    }
 $arr['fields']=$add_fields;   
}

return $arr;  
} 
public function create_field($field){
  //  return 'ok';
 
$name=isset($field['name']) ? $field['name'] : '';
$label=isset($field['label']) ? $field['label'] : '';
$type=isset($field['type']) ? $field['type'] : '';
$object=isset($field['object']) ? $field['object'] : '';

$error='Unknow error';
if(!empty($label) && !empty($type) && !empty($object)){

    $body=array('name'=>$name,'label'=>$label,'groupName'=> strtolower($object).'information','type'=>'string');
    $path='properties/v1/contacts/properties';
    if($object == 'Company'){ 
    $path='properties/v1/companies/properties';
    }
$arr=$this->post_hubspot_arr($path,'POST',$body); 

    $error='ok';
if(!isset($arr['name']) ){
  $error=$arr;  
 if(isset($arr['message'])){
 $error=$arr['message'];    
 }   
}
}
return $error;    
}  
}
}
?>