<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

if (! empty($_REQUEST['cust_id']) && ! empty($_REQUEST['field'])) {
    $id = $_REQUEST['cust_id'];
    $field = $_REQUEST['field'];

    $model = new ModelDef('EventsCustomer');
    $fields = array('id', $field);

    $lq = new ListQuery($model, $fields);
    $lq->addPrimaryKey();
    $lq->addAclFilter('edit');
    $lq->addFilterPrimaryKey($id);
    $result = $lq->runQuerySingle();

    if (! $result->failed && isset($result->row[$field])) {
        $upd = RowUpdate::for_model($model);
        $upd->limitFields($fields);

        $value = $result->row[$field];

        if ($value == 1) {
            $new_value = 0;
        } else {
            $new_value = 1;
        }

        if($upd->setOriginal($result)) {
            $upd->set(array($field => $new_value));
            $upd->save();
        }
    }
}

print 1;
exit;
?>
