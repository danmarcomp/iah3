/**
 * The contents of this file are subject to the SugarCRM Public License Version
 * 1.1.3 ("License"); You may not use this file except in compliance with the
 * License. You may obtain a copy of the License at http://www.sugarcrm.com/SPL
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied.  See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * All copies of the Covered Code must include on each user interface screen:
 *    (i) the "Powered by SugarCRM" logo and
 *    (ii) the SugarCRM copyright notice
 * in the same form as they appear in the distribution.  See full license for
 * requirements.
 *
 * The Original Code is: SugarCRM Open Source
 * The Initial Developer of the Original Code is SugarCRM, Inc.
 * Portions created by SugarCRM are Copyright (C) 2004-2006 SugarCRM, Inc.;
 * All Rights Reserved.
 * Contributor(s): ______________________________________.
 */


   
function JSTransaction(){
    this.JSTransactions = new Array();
    this.JSTransactionIndex = 0;
    this.JSTransactionCanRedo = false;
    this.JSTransactionTypes = new Array(); 
    

}

    JSTransaction.prototype.record = function(transaction, data){
        this.JSTransactions[this.JSTransactionIndex] = {'transaction':transaction , 'data':data};
        this.JSTransactionIndex++;
        this.JSTransactionCanRedo = false
    }
    JSTransaction.prototype.register = function(transaction, undo, redo){
        this.JSTransactionTypes[transaction] = {'undo': undo, 'redo':redo};
    }
    JSTransaction.prototype.undo = function(){
        if(this.JSTransactionIndex > 0){
            if(this.JSTransactionIndex > this.JSTransactions.length ){
                this.JSTransactionIndex  = this.JSTransactions.length;
            }
            var transaction = this.JSTransactions[this.JSTransactionIndex - 1];
            var undoFunction = this.JSTransactionTypes[transaction['transaction']]['undo'];
            undoFunction(transaction['data']);
            this.JSTransactionIndex--;
            this.JSTransactionCanRedo = true;
        }
    }
    JSTransaction.prototype.redo = function(){
        if(this.JSTransactionCanRedo && this.JSTransactions.length < 0)this.JSTransactionIndex = 0;
        if(this.JSTransactionCanRedo && this.JSTransactionIndex <= this.JSTransactions.length ){
            this.JSTransactionIndex++;
            var transaction = this.JSTransactions[this.JSTransactionIndex - 1];
            var redoFunction = this.JSTransactionTypes[transaction['transaction']]['redo'];
            redoFunction(transaction['data']);
        }
    }



