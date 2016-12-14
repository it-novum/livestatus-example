<?php
//Copyright (C) 2005-2016 it-novum GmbH
//
//This program is free software; you can redistribute it and/or modify it 
//under the terms of the GNU General Public License version 2 as published 
//by the Free Software Foundation.
//
//This program is distributed in the hope that it will be useful, 
//but WITHOUT ANY WARRANTY; without even the implied warranty of 
//MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  
//See the GNU General Public License for more details.

class Livestatus{
        
        public $socketPath = null;
        public $socket = null;
        public $format = 'OutputFormat: json'; //wrapped_json is only available in livestatus naemon version
        
        public function __construct($socketPath){
                $this->socketPath = $socketPath;
        }
        
        public function bind(){
                $this->socket = fsockopen('unix://'.$this->socketPath, NULL, $errno, $errstr, 5);
        }
        
        public function query($queryAsArray, $columns = array()){
                $this->bind();
                $query = $this->buildQuery($queryAsArray, $columns);
                fwrite($this->socket, $query);
                $response = trim(stream_get_contents($this->socket));
                $response = json_decode($response, true);
                //pr($response);
                
                $response = $this->parseResult($response, $columns);
                $this->close();
                return $response;
        }
        
        public function parseResult($result, $columns){
                if($result === NULL){
                        throw new Exception('NULL given, livestatus result empty?');
                        return;
                }
                
                if(!is_array($result)){
                        throw new Exception('String given, livestatus result empty?');
                        return;
                }
                
                if(empty($result)){
                        throw new Exception('Empty ivestatus result');
                        return;
                }
                
                $return = array();
                
                if(empty($columns)){
                        //Select *, first record are the columns
                        $columns = $result[0];
                        unset($result[0]);
                }
                
                foreach($result as $key => $data){
                        foreach($data as $columnKey => $value){
                                if(isset($columns[$columnKey])){
                                        $arrayKey = $columns[$columnKey];
                                        $return['data'][$key][$arrayKey] = $value;
                                }
                        }
                }
                
                foreach($return['data'] as $key => $data){
                        foreach($data as $columnName => $value){
                                switch($columnName){
                                        case 'members_with_state':
                                        case 'hostgroup_members_with_state':
                                        case 'servicegroup_members_with_state':
                                        foreach($return['data'][$key][$columnName] as $memberKey => $membersData)
                                        $return['data'][$key][$columnName][$memberKey] = array(
                                                'name' => $membersData[0],
                                                'state' => $membersData[1],
                                                'has_been_checked' => $membersData[2]
                                        );
                                        break;
                                }
                        }
                }
                
                return $return;
        }
        
        public function close(){
                fclose($this->socket);
        }
        
        function buildQuery($_query, $columns){
                $query = '';
                if(!is_array($_query)){
                        $_query = array($_query);
                }
                foreach($_query as $line){
                        $query .= $line.PHP_EOL;
                }
                
                if(!empty($columns)){
                        $query.= 'Columns: '.implode(' ', $columns).PHP_EOL;
                }
                $query .= $this->format.PHP_EOL;
                
                return $query.PHP_EOL;
        }
        
}

