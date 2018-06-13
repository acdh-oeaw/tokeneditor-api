<?php

/**
 * The MIT License
 *
 * Copyright 2016 Austrian Centre for Digital Humanities.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace acdhOeaw\tokeneditorApi;

use PDO;
use RuntimeException;
use stdClass;
use ZipArchive;
use acdhOeaw\tokeneditorApi\util\BaseHttpEndpoint;
use acdhOeaw\tokeneditorApi\Document;
use acdhOeaw\tokeneditorModel\Editor as mEditor;
use zozlak\rest\DataFormatter;
use zozlak\rest\HeadersFormatter;
use zozlak\rest\HttpController;
use zozlak\util\DbHandle;

/**
 * Description of Document
 *
 * @author zozlak
 */
class Editor extends BaseHttpEndpoint {

    protected $documentId;
    protected $editorId;
    protected $path;
    protected $controller;
    
    public function __construct(stdClass $path, HttpController $controller) {
        $this->path = $path;
        $this->controller = $controller;
        $this->editorId = urldecode($this->editorId);
        parent::__construct($path, $controller);
    }
    
    public function get(DataFormatter $f, HeadersFormatter $h) {
        $doc = new mEditor(DbHandle::getHandle());
        
        $data = $doc->getEditor($this->documentId, $this->editorId);
        if(count((array)$data) > 0){
            $editors = array();
            if($data->user_id == $this->editorId){
                $editors =  $data;
            }
        } else {
            throw new RuntimeException('There is no document editor with this id: '.$this->editorId, 400);
        }
        
        $f->initCollection();
        
        if(count($editors) > 0){ 
            $f->append($editors); 
        }else{
            throw new RuntimeException('There is no document editor with this id: '.$this->editorId, 400);
        }
        $f->closeCollection();
    }
    /**
     * 
     * If the editor id is the owner then delete the document
     * 
     * @param DataFormatter $f
     * @param HeadersFormatter $h
     */
    public function delete(DataFormatter $f, HeadersFormatter $h) {
        $doc = new mEditor(DbHandle::getHandle());
        
        //chech the user in the database
        $data = $doc->getEditor($this->documentId, $this->editorId);
        
        //if we have the user then we need to try to delete it
        if(count((array)$data) > 0){
            
            //if the user id from the DB and the API editor id is the same then do the delete
            if($data->user_id == $this->editorId){

                $pdo = DbHandle::getHandle();
                $query = $pdo->prepare("DELETE FROM documents_users
                        WHERE document_id = :docid and user_id = :userid
                ");

                $query->bindParam(':docid', $this->documentId, PDO::PARAM_INT);
                $query->bindParam(':userid', $this->editorId, PDO::PARAM_STR);
                $query->execute();

                //if the delete was succesfull
                if($query->rowCount() > 0){
                    $f->data(['documentId' => $this->documentId]);
                }else{
                    throw new RuntimeException('Error during the editor delete : '.$this->editorId.' Document id: '.$this->documentId, 400);
                }
            }
            
        }else {
            throw new RuntimeException('There is no document editor with this id: '.$this->editorId, 400);
        }
    }
    
    public function put(DataFormatter $f, HeadersFormatter $h) {
        
        $doc = new mEditor(DbHandle::getHandle());
        $name = "";
        
        //if we have the nicename in the params
        if(!empty(filter_input(INPUT_GET, 'name'))){
            $name = urldecode(filter_input(INPUT_GET, 'name'));
        }
        
        //check the user is already added to the document
        $data = $doc->getEditor($this->documentId, $this->editorId);
        
        //the user is not exists in for this document
        if(count((array)$data) == 0) {
            //add the editor to the document
            if( $doc->addEditor($this->documentId, $this->editorId, $name) === true){
                $f->data(['documentId' => $this->documentId]);
            }else {
                throw new RuntimeException("ERROR!! Edtitor wasn't added! Docid: ".$this->documentId. " Editor: ".$this->editorId, 400);
            }
        } else {
            throw new RuntimeException('This editor is already added to this Document', 400);
        }
    }
}