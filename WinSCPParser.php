<?php

class WinSCPParser {
    private const WSCP_SIMPLE_MAGIC = 0xA3;
    private const WSCP_SIMPLE_FLAG = 0xFF;
    private const WSCP_SIMPLE_STRING = '0123456789ABCDEF';

    private $content;

    public function __construct($filePath) {
        $this->content = file_get_contents($filePath);
    }

    public function getFolders() {
        $folders = [];
        $lines = explode("\n", $this->content);
        
        foreach ($lines as $line) {
            if (preg_match('/^\[Sessions\\\\(.+?)\/[^\/]+\]/', $line, $matches)) {
                $folder = $matches[1];
                $parts = explode('/', $folder);
                $path = '';
                
                foreach ($parts as $part) {
                    $path = $path ? $path . '/' . $part : $part;
                    if (!in_array($path, $folders)) {
                        $folders[] = $path;
                    }
                }
            }
        }
        
        sort($folders);
        return $folders;
    }

    private function simple_decrypt_next_char(&$chars) {
        if (count($chars) == 0) {
            return 0x00;
        }
        
        $a = strpos(self::WSCP_SIMPLE_STRING, array_shift($chars));
        $b = strpos(self::WSCP_SIMPLE_STRING, array_shift($chars));
        
        return self::WSCP_SIMPLE_FLAG & ~(((($a << 4) + $b) << 0) ^ self::WSCP_SIMPLE_MAGIC);
    }

    private function decryptPassword($hostname, $username, $encrypted) {
        if (!preg_match('/^[A-F0-9]+$/', $encrypted)) {
            return '';
        }

        $result = [];
        $key = $username . $hostname;
        $chars = str_split($encrypted);

        $flag = $this->simple_decrypt_next_char($chars);
        
        if ($flag == self::WSCP_SIMPLE_FLAG) {
            $this->simple_decrypt_next_char($chars);
            $length = $this->simple_decrypt_next_char($chars);
        } else {
            $length = $flag;
        }
        
        $shift = $this->simple_decrypt_next_char($chars);
        $chars = array_slice($chars, $shift * 2);
        
        for ($i = 0; $i < $length; $i++) {
            $result[] = chr($this->simple_decrypt_next_char($chars));
        }
        
        if ($flag == self::WSCP_SIMPLE_FLAG) {
            $valid = implode('', array_slice($result, 0, strlen($key)));
            if ($valid != $key) {
                return '';
            }
            $result = array_slice($result, strlen($key));
        }
        
        return implode('', $result);
    }

    private function cleanString($str) {
        // Remove non-printable characters
        $clean = preg_replace('/[\x00-\x1F\x7F]/u', '', $str);
        return "'" . trim($clean) . "'";
    }

    public function getItems() {
        $items = [];
        $lines = explode("\n", $this->content);
        $currentSession = null;
        $currentData = null;
        
        foreach ($lines as $line) {
            if (preg_match('/^\[Sessions\\\\(.+)\]/', $line, $matches)) {
                if ($currentSession && $currentData) {
                    $this->processSession($items, $currentSession, $currentData);
                }
                
                $currentSession = $matches[1];
                $currentData = [];
            } elseif ($currentSession && preg_match('/^(\w+)=(.*)$/', trim($line), $matches)) {
                $currentData[$matches[1]] = $matches[2];
            }
        }
        
        if ($currentSession && $currentData) {
            $this->processSession($items, $currentSession, $currentData);
        }
        
        return $items;
    }

    private function processSession(&$items, $currentSession, $currentData) {
        $folder = dirname(str_replace('\\', '/', $currentSession));
        $folder = ($folder === '.') ? '' : $folder;
        
        if (!isset($items[$folder])) {
            $items[$folder] = [];
        }
        
        if (isset($currentData['HostName']) && isset($currentData['UserName']) && isset($currentData['Password'])) {
            $currentData['Password'] = $this->decryptPassword(
                $currentData['HostName'],
                $currentData['UserName'],
                $currentData['Password']
            );
        }

        $items[$folder][] = [
            'HostName' => $currentData['HostName'],
            'PortNumber' => (int)$currentData['PortNumber'],
            'UserName' => $this->cleanString($currentData['UserName']),
            'Password' => $this->cleanString($currentData['Password']),
            'Name' => $this->cleanString(urldecode(basename($currentSession))),
            'Protocol' => 'Protocol.Sftp'
        ];
    }
}
