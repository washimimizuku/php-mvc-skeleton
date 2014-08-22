<?php

/**
 * A Debug Log class 
 *
 * @author Julien Hoarau <jh@datasphere.ch>
 */
class DebugLog {
    /////////////////////////////////////////
    //          ATTRIBUTES
    /////////////////////////////////////////
    
    // CONSTANTS
        // Format
        const DELIM_1           = '=';
        const DELIM_2           = '-';
        const LOG_WIDTH         = 120;

        // Available log types
        const LOG_COMMON        = 'COMMON';
        const LOG_DEBUG         = 'DEBUG';
        
        // Available log files
        const FILE_DEBUG        = 'Debug';
        
    // PRIVATE
        private $logsDir;
    
    // PROTECTED
        protected $aLogs;
        protected $aLogFiles;
        protected $timerReference;
    
    // PUBLIC
        // Format
        public $lineBreak;
        public $tab;
        public $nbTab;
    
    // STATIC
        protected static $instance;
    
    /**
        * Class's constructor
        * @access protected
        * @author Julien Hoarau <jh@datasphere.ch>
        * @return void
        */
    protected function __construct()
    {        
        // init
        $this->logsDir                  = getenv('app_root').'/logs/';
        $this->aLogs                    = array();
        $this->lineBreak                = chr(13).chr(10);
        $this->tab                      = chr(9);
        $this->nbTab                    = 6;
        $this->timerReference           = microtime(true);
        
        $this->aLogFiles[self::LOG_DEBUG]		= $this->getLogFile(self::FILE_DEBUG);
        
        $this->writeHeader();
        $this->writeGetParameters();
    }
    
    /**
        * Get the singleton instance
        * (construct if needed)
        * @access public
        * @author Julien Hoarau <jh@datasphere.ch>
        * @return DebugLog   $instance
        */
    public static function getInstance()
    {
        // check instance and construct if needed
        if (ApplicationConfig::getInstance()->isDev AND !isset(self::$instance)) {
            self::$instance = new DebugLog();
        }
        return self::$instance;
    }
    
    /**
        * Class's destructor
        * @access public
        * @author Julien Hoarau <jh@datasphere.ch>
        * @return void
        */
    public function __destruct()
    {
        if (count($this->aLogs) > 0) {
            $this->logTimer('SCRIPT DURATION');
            
            $memory         = memory_get_usage();
            $memoryReal     = memory_get_usage(true);
            $memoryPeak     = memory_get_peak_usage();
            $memoryRealPeak = memory_get_peak_usage(true);
            $sLog   = round(($memory/1024)/1024, 5)."M -- PEAK: ".round(($memoryPeak/1024)/1024, 5)."M (REAL = ".round(($memoryReal/1024)/1024, 5)."M -- REAL_PEAK: ".round(($memoryRealPeak/1024)/1024, 5)."M Max :".ini_get('memory_limit').")";
            
            $this->logMessage(self::LOG_DEBUG, 'SCRIPT MEMORY -- '.$sLog);
            $this->writeLogFile();
        }
    }
    
    /**
        * Get log file's name (create if needed)
        * @access protected
        * @author Julien Hoarau <jh@datasphere.ch>
        * @param String $type 
        * @param String $dirLog     If null, $this->logsDir is used
        * @return Mixed     Log file(s)
        */
    protected function getLogFile($type = NULL, $dirLog = NULL)
    {
        // init
        $dirLog = ($dirLog) ? $dirLog : $this->logsDir;

        if (!is_null($type)) {
                $logFilePath    = $dirLog.'log'.ucfirst($type).'.log';

                // if file dosn't exists, we create it
                if (!is_file($logFilePath)) {
                    FileManager::createFile($logFilePath, 'w+');
                }
                return $logFilePath;

        } else {
                $prefix         = 'FILE_';
                $self            = new ReflectionClass($this);
                $aLogs    = array();

                foreach ($self->getConstants() AS $label => $const) {
                        if (substr($label, 0, 5) == $prefix) {
                                $logFilePath	= $dirLog.'log'.ucfirst($const).'.log';
                                $aLogs[]	= $logFilePath;
                                
                                // if file dosn't exists, we create it
                                if (!is_file($logFilePath)) {
                                        FileManager::createFile($logFilePath);
                                }
                        }
                }
                return $aLogs;
        }
    }
    
    /**
        * Write all messages into log file(s)
        * @access protected
        * @author Julien Hoarau <jh@datasphere.ch>
        * @param Array $aLog    If null, $this->aLogs is used
        * @return void
        */
    public function writeLogFile(Array $aLog = null)
    {
        // init
        $aLog       = ($aLog !== null) ? $aLog : $this->aLogs;
        $sDebugLog  = '';
        // TODO : Maybe other logFiles ?
        
        if (is_array($aLog) AND count($aLog)) {
            foreach ($aLog as $log) {
                switch ($log['type']) {
                    case self::LOG_DEBUG : {
                        $sDebugLog	       .= $log['msg'].$this->lineBreak;
                        break;
                    }
                    // TODO : Maybe other logFiles ?
                    
                    case self::LOG_COMMON : {
                        if ($this->getPresenceLogByType($aLog, self::LOG_DEBUG)) {
                            $sDebugLog       .= $log['msg'].$this->lineBreak;
                        }
                        
                        // TODO : Maybe other logFiles that need common logs?
                        break;
                    }
                    default: {
                        break;
                    }
                }
            }
        }
        
        // UTF8-encode
        $sDebugLog	= utf8_encode($sDebugLog);
        
        if ($sDebugLog != '') {
            error_log($sDebugLog, 3, $this->aLogFiles[self::LOG_DEBUG]);
        }
    }
    
    /**
        * Check the presence of logsof the specified type
        * @access protected
        * @author Julien Hoarau <jh@datasphere.ch>
        * @param Array  $aLog	Logs Array
        * @param String  $type	Type that will be checked
        * @return Boolean Presence or not of logs of the specified type
        */
    protected function getPresenceLogByType($aLog, $type)
    {
        // init
        $isPresent = false;

        if ($type) {
            foreach ($aLog as $log) {
                if ($log['type'] == $type) {
                    $isPresent = true;
                    break;
                }
            }
        }

        return $isPresent;
    }
    
    /**
        * Log the execution time 
        * @access public
        * @author Julien Hoarau <jh@datasphere.ch>
        * @param  String $timerReference Timestamp (microtime(true)) (optionnal) 
        * @return Void
        */
    public function logTimer($label = '', $timerReference = null)
    {
        if ($timerReference === null) {
            $timerReference = $this->timerReference;
        }
        
        // Init
        $executionTime      = round(microtime(true) - $timerReference, 4);
        $logTimer                = '';
        if ((float) $executionTime > 0.00000001) {
            $logTimer            .= $label.' -- '.$executionTime.' seconds ';
            
            $this->debug($logTimer);
        }
    }
    
    
    /**
        * Add log header
        * @access private
        * @author Julien Hoarau <jh@datasphere.ch>
        * @return void
        */
    private function writeHeader()
    {
        // init
        $scriptName				= ' '.$_SERVER['REQUEST_URI'].' ';

        // format
        $formatedScriptName  = $this->formatHeader($scriptName);
        
        // Write header in common logs
        $this->logMessage(self::LOG_COMMON, $this->lineBreak.str_repeat(self::DELIM_1, self::LOG_WIDTH), FALSE);
        $this->logMessage(self::LOG_COMMON, $formatedScriptName, FALSE);
    }
    
    /**
        * Format the header
        * @access protected
        * @author Julien Hoarau <jh@datasphere.ch>
        * @param String $scriptName
        * @return String formatedScriptName.
        */
    protected function formatHeader($scriptName)
    {
        $formatedScriptName     = $scriptName;
        $nbChar                             = round((self::LOG_WIDTH - (strlen($formatedScriptName))) / 2);
        
        // if the scriptName doesn't take all the space
        if ($nbChar > 0 ){
            
            // odd to even-length string
            if ($nbChar %2 != 0) {
                $formatedScriptName	   .= ' ';
                $nbChar --;
            }

            // filling remaining space
            $sFiller = str_repeat('=', $nbChar);
            $formatedScriptName = $sFiller.$formatedScriptName.$sFiller;
        }

        return $formatedScriptName;
    } 
    
    private function writeGetParameters()
    {
        $this->debugNonScalarObject($_GET, '----- GET PARAMETERS -----', FALSE);
        $this->logMessage(self::LOG_DEBUG, '--------------------------', FALSE);
    }
    
    /**
        * Format the beginning line shift 
        * @access protected
        * @author Julien Hoarau <jh@datasphere.ch>
        * @return String
        */
    protected function formatShift()
    {
        $sShift = str_repeat($this->tab, $this->nbTab);
        return $sShift;     	
    }
    
    /**
        * Format a date string to display in log file
        * @access protected
        * @author Julien Hoarau <jh@datasphere.ch>
        * @return String
        */
    protected function formatDate()
    {
        return '['.date('d-m-Y H:i:s').']';
    }
    
    /**
        * Write a debug message 
        * @access public
        * @author Julien Hoarau <jh@datasphere.ch>
        * @param String $msg 
        * @return void
        */
    public function debug($msg)
    {
        // init
        $replace	= $this->lineBreak . ' ' . $this->formatShift();
        
        // replace line Breaks
        $msg		= str_replace($this->lineBreak, chr(10), $msg);
        $msg		= str_replace(chr(10), $replace, $msg);

        // add to logs array
        $this->logMessage(self::LOG_DEBUG, trim($msg));
    }
   
    /**
        * Write a debug message for non scalar content
        * @access public
        * @author Julien Hoarau <jh@datasphere.ch>
        * @param Object|Array $content
        * @param String $label
        * @param Integer $maxIteration
        * @return void
        */
    public function debugNonScalarObject($content, $label = '', $isDate = true, $maxIteration = 99)
    {
        // init
        $msg                = '';

        if ($label) {
                $msg        = $label;
                if (is_array($content) OR is_object($content)) {
                        $msg  .= $this->lineBreak;
                }
        }

        // format
        $msg    .= $this->formatNonScalarObject($content, 0, $maxIteration);
        
         // replace line Breaks
        $replace       = $this->lineBreak . ' ' . $this->formatShift();
        $msg            = str_replace($this->lineBreak, chr(10), $msg);
        $msg            = str_replace(chr(10), $replace, $msg);

        // add to logs array
        $this->logMessage(self::LOG_DEBUG, trim($msg), $isDate);
    }

    /**
        * Recursive function to format a non scalar object
        * @access protected
        * @author Julien Hoarau <jh@datasphere.ch>
        * @param Array $content 
        * @param Integer $nbIteration
        * @param Integer $maxIteration
        * @return String
        */
    protected function formatNonScalarObject($content, $nbIteration, $maxIteration)
    {
        if ($nbIteration > $maxIteration) {
                return '';
        }

        // init
        $msg		= '';
        
        // generate a shift depending on current iteration
        $shift	= str_repeat($this->tab, $nbIteration);

        if (is_array($content) OR is_object($content)) {
                foreach ($content as $attributeName => $attributeValue) {

                        // add shift
                        $msg        .= $shift;
                        $nbTabs     = floor((30 - strlen($attributeName) - 1) / 4);
                        $nbTabs     = ($nbTabs < 0) ? 0 : $nbTabs;
                        if (is_array($attributeValue) OR is_object($attributeValue)) {
                                $msg   .= '['.$attributeName.'] = ['.$this->lineBreak.$this->formatNonScalarObject($attributeValue, ($nbIteration + 1), $maxIteration).$this->lineBreak;
                        } else {
                                if ($attributeValue === NULL) {
                                        $attributeValue = 'NULL';
                                } else if ($attributeValue === TRUE) {
                                        $attributeValue = 'TRUE';
                                } else if ($attributeValue === FALSE) {
                                        $attributeValue = 'FALSE';
                                }

                                $msg   .= '['.$attributeName.']'.str_repeat($this->tab, $nbTabs).'= '.$attributeValue.$this->lineBreak;
                        }
                }
                if ($nbIteration != 0) {
                        $msg       .= str_repeat($this->tab, $nbIteration-1).']';
                }
        } else {
                if ($content === NULL) {
                        $msg		= 'NULL';
                } else if ($content === TRUE) {
                        $msg		= 'TRUE';
                } else if ($content === FALSE) {
                        $msg		= 'FALSE';
                } else {
                        $msg       .= $content;
                }
        }

        return $msg;
    }
    
    /**
        * Add a log message
        * @access public
        * @author Julien Hoarau <jh@datasphere.ch>
        * @param String $logType    Log type can be :
        *       - DebugLog::LOG_COMMON
        *       - DebugLog::LOG_DEBUG 
        * @param String $msg           Message to write in logFile
        * @param boolean $isDate   Add date in the beginning of line (Default : true)
        * @return void
        */
    public function logMessage($logType, $msg, $isDate = true)
    {
        if ($this->getLogAvailability($logType)) {
            if ($isDate) {
                // Add date in the beginning of line
                $msg	= $this->formatDate().' - '.$msg;
            }

            // Push log message into the log array
            array_push($this->aLogs, array('type' => $logType, 'msg' => $msg));
        }
    }
    
    protected function getLogAvailability($logType) 
    {
        $isAvailable = true;
        
        $availableLogTypes = array(self::LOG_COMMON, self::LOG_DEBUG);
        if (!in_array($logType, $availableLogTypes)) {
            $isAvailable = false;
        }
        
        return $isAvailable;
    }
}

?>
