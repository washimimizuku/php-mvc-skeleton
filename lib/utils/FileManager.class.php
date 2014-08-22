<?php

/**
 * A file manager class
 * @author Julien Hoarau <jh@datasphere.ch>
 */
class FileManager {
    
    // CONSTANTS
        // flags		
        const FLAG_ADDSLASHES		= 'ADDSLASHES';
        const FLAG_HTTP_PATH		= 'HTTP_PATH';
    
        
    /**
        * Create a directory with a specified path
        * @access public
        * @static
        * @author Julien Hoarau <jh@datasphere.ch>
        * @param string $path
        * @return bool $success.
        */
    public static function createDirectory($path)
    {
        // init
        $path	= self::normalizePath($path);

        // creation (if needed)
        if (!is_dir(realPath($path))) {
                mkdir($path, 0777, true);
        }

        return is_dir($path);
    }
    
    /**
        * Create a file with a specified path
        * @access public
        * @static
        * @author Julien Hoarau <jh@datasphere.ch>
        * @param string $path 
        * @param string $mode File mode
        * @return bool $success
        */
    public static function createFile($path, $mode = null)
    {
        // init
        $mode       = ($mode !== null) ? $mode : 'w';
        $filePath   = self::normalizePath($path);
        $dirPath    = dirname($filePath);
        
        if (!is_dir($dirPath)) self::createDirectory($dirPath);
        
        if (is_dir($dirPath) AND !is_file($filePath)) {
            $file   = fopen($filePath, $mode);
            fclose($file);
            $s = chmod($filePath, 0777);
        }
        
        return is_file($filePath);
    }
    
    /**
        * Write to a file (and create all the path if needed)
        * @access public
        * @static
        * @author Julien Hoarau <jh@datasphere.ch>
        * @param string $file 
        * @param scalar $content 
        * @return bool $success.
        */
    public static function writeToFile($file, $content)
    {
        $return = false;
        
        $isDir = self::createDirectory(dirname($file));
        if ($isDir)  $return = file_put_contents($file, $content, FILE_APPEND|LOCK_EX);
        
        return (bool) $return;
    }
    
    /**
        * Normalize the specified folder path
        * @access public
        * @static
        * @author Julien Hoarau <jh@datasphere.ch>
        * @param string $path Folder Path to normalize
        * @param string|array $flags String or Array that define some special handling for the return string <br/>
        * Available flags are : <br/>
        *   - self::FLAG_ADDSLASHES : add a slah at the end.<br/>
        *   - self::FLAG_HTTP_PATH : Format the path for an http usage
        * @return string Normalized path
        */
    public static function normalizePath($path, $flags = null)
    {
        $DS = DIRECTORY_SEPARATOR; // directory separator
        $RDS = ($DS == '/') ? '\\' : '/'; // reverse directory separator
        $NTDS = '\\\\'; // network directory separator

        $flags = (array)$flags;
        $isNetworkPath = (substr($path, 0, 2) === $NTDS);

        /**
            * It's a 2 phases separator replacement :
            * 1) replace directories separators ( "/" or "\" ) by "/" 
            * 2) replace every successive separators by a single DIRECTORY_SEPARATOR
            */
        $path = preg_replace('/(?:\/)+/', $DS, preg_replace('/(?:\\\)+|(?:\/)+/', '/', $path));

        // flags
        foreach ($flags AS $flag) {
                switch ($flag) {
                        case self::FLAG_ADDSLASHES : {
                                // add a slash at the end
                                $path .= (substr($path, -1) !== $DS) ? $DS : '';
                                break;
                        }
                        case self::FLAG_HTTP_PATH : {
                                // replace \ by /
                                $path = str_replace($DS, '/', $path);
                                break;
                        }
                }
        }

        // reformat as network path
        if ($isNetworkPath) {
                $path = substr($path, 1);
                $path = $NTDS.$path;
        }

        return $path;
    }
    
}

?>
