/* 
 * The MIT License
 *
 * Copyright 2015 zozlak.
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

/**
 * 
 * Sample code for embeding TokenEditorIporter on you site:
 *  $().ready(function(){
 *      var tmp = new TokenEditorImporter(
 *          $('#myDivElement').get(0), 
 *          'http://127.0.0.1/tokeneditor/document',
 *          function(data){
 *              console.log(data);
 *          }
 *      );
 *  });
 * 
 * @param {type} domElement DOM node in which import form should be created
 * @param {type} apiUrl API URL, typically https://clarin.oeaw.ac.at/tokenEditor/document
 * @param {type} callback function to be called on sucessful import
 * @returns {TokenEditorImporter}
 */
TokenEditorImporter = function (domElement, apiUrl, callback) {
    var that = this;
    this.dom = domElement;
    this.apiUrl = apiUrl;
    this.callback = callback;
    $(this.dom).html(
        '<form method="post" enctype="multipart/form-data">' +
            '<input type="hidden" name="MAX_FILE_SIZE" value="100000000"/>' +
            '<div class="form-group">' +
                '<label for="documentName">Document name</label> ' +
                '<input type="text" name="name" required="required" placeholder="Document name" class="form-control" id="documentName"/>' +
            '</div>' +
            '<div class="form-group">' +
                '<label for"documentFile">Data file</label> ' +
                '<input type="file" name="document" accept="text/xml" required="required" class="" id="documentFile"/>' +
            '</div>' +
            '<div class="form-group">' +
                '<label for="documentSchema">Schema file</label> ' +
                '<input type="file" name="schema" accept="text/xml" required="required" class="" id="documentSchema"/>' +
            '</div>' +
            '<button type="submit" class="btn btn-primary">Import document</button>' +
        '</form>'
    );
    $(this.dom).find('form').submit(function () {
        $.ajax({
            url: that.apiUrl,
            type: 'POST',
            data: new FormData(this),
            processData: false,
            contentType: false,
            success: that.callback
        });
        return false;
    });
};
