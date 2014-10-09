<?php
class WrapperOauth extends \Tonic\Resource {
    
    protected $server;
    protected $storage;
    

    /**
     * to use this class u need to have the foollowing tables:
     * CREATE TABLE oauth_clients (client_id VARCHAR(80) NOT NULL, client_secret VARCHAR(80) NOT NULL, redirect_uri VARCHAR(2000) NOT NULL, grant_types VARCHAR(80), scope VARCHAR(100), user_id VARCHAR(80), CONSTRAINT client_id_pk PRIMARY KEY (client_id));
     * INSERT INTO oauth_clients (client_id, client_secret, redirect_uri) VALUES ("testclient", "testpass", "http://fake/");
     * 
     * CREATE TABLE oauth_scopes (scope TEXT, is_default BOOLEAN);
     * 
     * CREATE TABLE oauth_access_tokens (access_token VARCHAR(40) NOT NULL, client_id VARCHAR(80) NOT NULL, user_id VARCHAR(255), expires TIMESTAMP NOT NULL, scope VARCHAR(2000), CONSTRAINT access_token_pk PRIMARY KEY (access_token));
     * 
     * CREATE TABLE oauth_refresh_tokens (refresh_token VARCHAR(40) NOT NULL, client_id VARCHAR(80) NOT NULL, user_id VARCHAR(255), expires TIMESTAMP NOT NULL, scope VARCHAR(2000), CONSTRAINT refresh_token_pk PRIMARY KEY (refresh_token));
     * 
     * @param \Tonic\Application $app
     * @param \Tonic\Request $request
     */
    public function __construct(\Tonic\Application $app, \Tonic\Request $request) {
        parent::__construct($app, $request);
        
        //para testar é preciso enviar um header adicional
        //Ir pagina http://www.base64encode.org/, meter no encode, testclient:testpass
        //Authorization: Basic encode(TestClient:TestSecret)
        //Authorization: Basic VGVzdENsaWVudDpUZXN0U2VjcmV0
        
        //second stage
        //curl http://10.0.0.169/~hpereira/testes/oauth2/resource.php -d 'access_token=YOUR_TOKEN'
            
        $config     = ApplicationConfig::getInstance();
        $db         = $config->mysqlDatabase;
        $hostMysql  = $config->mysqlHost['write'][0];
        $dbUser     = $config->mysqlUser;
        $dbPwd      = $config->mysqlPassword;
        
        
        //create and configure our OAuth2 Server object
            
            $dsn = "mysql:dbname=".$db.";host=".$hostMysql;
            $dbInfo = array('dsn' => $dsn, 'username' => $dbUser, 'password' => $dbPwd);
            $tableInfoOauth = array('user_table' => 'users');
            $this->storage = new wrapperPdo($dbInfo, $tableInfoOauth);

            
            // Pass a storage object or array of storage objects to the OAuth2 server class
            $this->server = new \OAuth2\Server($this->storage);
            
            
            // Add the "User Credentials" grant type 
            $this->server->addGrantType(new \OAuth2\GrantType\UserCredentials($this->storage));
        //create and configure our OAuth2 Server object
               
                
                
        
    }


    /**
     * Validate if user has the correct credentials
     * 
     * @return array with access token or error
     */
    public function auth() {  
        // Handle a request for an OAuth2.0 Access Token and send the response to the client
        $response = array();
        
        
        $infoUser = \OAuth2\Request::createFromGlobals();
        //ADICIONAR O TYPE DE GRANT AQUI
        $infoUser->request['grant_type'] = 'password';
       
        
        $auth = (array) json_decode($this->server->handleTokenRequest($infoUser)->getResponseBody('json'));
        $response['response'] = $auth;
        
        return $response;
    }
    
    public function access(){
        
        //TODO: UPDATE THIS BELOW, SO A PERSON DOES NOT HAVE TO UNSET VALUES
        //REMOVE AUTHORIZATION HEADER, SO A PERSON CAN TEST USING "POSTMAN" OR OTHERS
        $globalsOrig = \OAuth2\Request::createFromGlobals() ;
        $globals = $globalsOrig;
        
        
        // Handle a request for an OAuth2.0 Access Token and send the response to the client
        if (!$this->server->verifyResourceRequest($globals)) {
            $infoAccess = json_decode($this->server->getResponse()->getResponseBody('json'), true);
            $access = empty($infoAccess) ? 'Problems with access token' : $infoAccess['error_description'];

            return array('status' => 'error', 'message' => $access);  
        }else{
           
            
            //VALIDATE IF USER HAS PERMISSIONS TO DO WHAT HE WANTS 
                //GET USER EMAIL 
                $arrInfoUser = $this->getAccessTokenDataUserLogged();
                $userEmailLogged = $arrInfoUser['user_id'];
                $userEmailPwdLogged = $arrInfoUser['password'];

                //CONTROLLER
                    //GET METHOD REQUESTED TO KNOW IF IT IS TO ADD/DEL/VIEW/EDIT
                    $infoOauth = \OAuth2\Request::createFromGlobals();
                    $infoMethod = $infoOauth->server['REQUEST_METHOD'];
                    //GET NAME CONTROLLER, TO SEE WHERE TO LOOK AT
                    $nameFileExecuted = str_replace('REST', '', get_class($this));
                    //FLAG TO CHEK WHEN IS METHOD "GET", ONLY ONE OR MORE
                    $flagOnlyOneGet = strtolower($infoMethod) == 'get' && isset($this->id) && !empty($this->id) ? 1 : 0 ;
                   
                
                
                $infoController = array(
                                "nameController" => $nameFileExecuted,
                                "actionController" => $infoMethod, 
                                "flagGetOnlyOne" => $flagOnlyOneGet
                                );
                $infoUser = array(
                                "userEmail" => $userEmailLogged, 
                                "userPwd"   => $userEmailPwdLogged
                );
                
               
                if(Permissions::getPermissionsUser($infoController, $infoUser) == 'ok' ){
                    //'You accessed my APIs!'
                    return true;
                } else {
                    //PROBLEMS WITH PERMISSIONS
                    return array('status' => 'error', 'message' => 'User does not have permissions');  
                }
            //VALIDATE IF USER HAS PERMISSIONS TO DO WHAT HE WANTS 
        }
    }
    
    public function getAccessTokenDataUserLogged(){
        $tokenInfo = $this->server->getAccessTokenData(OAuth2\Request::createFromGlobals());
        return $this->storage->getUserDetails($tokenInfo['user_id']);
    }
            
            
}

/**
 * CLASS TO PUT OAUTH2 WORKING WITH ADSERVER TABLES/CREDENTIALS 
 * 
 */
class wrapperPdo extends \OAuth2\Storage\Pdo{
    
    /**
     * USED TO VALIDATE FIELDS WITH ADSERVER FUNCTIONS
     * 
     * @param type $username
     * @param type $password
     * @return boolean
     */
    public function checkUserCredentials($username, $password)
    {
        if ($user = $this->getUser($username)) {
            return User_Manager::check($password, $user['password']);
        }

        return false;
    }
    
    /**
     * ADDED CREATED FIELD TO TABLE
     * 
     * @param type $access_token
     * @param type $client_id
     * @param type $user_id
     * @param type $expires
     * @param type $scope
     * @return type
     */
    public function setAccessToken($access_token, $client_id, $user_id, $expires, $scope = null)
    {
        $date_created = date('Y-m-d H:i:s');
         
        // convert expires to datestring
        $expires = date('Y-m-d H:i:s', $expires);

        // if it exists, update it.
        if ($this->getAccessToken($access_token)) {
            $stmt = $this->db->prepare(sprintf('UPDATE %s SET client_id=:client_id, expires=:expires, user_id=:user_id, scope=:scope where access_token=:access_token', $this->config['access_token_table']));
        } else {
            $stmt = $this->db->prepare(sprintf('INSERT INTO %s (date_created, access_token, client_id, expires, user_id, scope) VALUES (:date_created, :access_token, :client_id, :expires, :user_id, :scope)', $this->config['access_token_table']));
        }

        return $stmt->execute(compact('date_created', 'access_token', 'client_id', 'user_id', 'expires', 'scope'));
    }

    /**
     * CHANGED NAME FIELD TO VALIDATE
     * 
     * @param type $username
     * @return boolean
     */
    public function getUser($username){
        
        $stmt = $this->db->prepare($sql = sprintf('SELECT * from %s where email=:username', $this->config['user_table']));
        $stmt->execute(array('username' => $username));
        
        

        if (!$userInfo = $stmt->fetch()) {
            return false;
        }

        // the default behavior is to use "username" as the user_id
        return array_merge(array(
            'user_id' => $username
        ), $userInfo);
    }
}


/**
 * CHANGED SO THE USER DOES NOT HAVE TO PUT CLIENT ID OR CLIENT SECRET
TESTES
use \OAuth2\RequestInterface;
use \OAuth2\ResponseInterface;

class WrappeUserCredentials extends \OAuth2\GrantType\UserCredentials{

} */


