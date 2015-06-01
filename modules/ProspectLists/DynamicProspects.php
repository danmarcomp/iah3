<?php
require_once('include/layout/forms/FilterForm.php');

class DynamicProspects {

    /**
     * Get dynamic prospect lists types
     *
     * @static
     * @return array
     */
    static function getTypes() {
        $types = array(
            'targets' => array('label' => 'LBL_TARGETS_FILTERS', 'module' => 'Prospects'),
            'contacts' => array('label' => 'LBL_CONTACTS_FILTERS', 'module' => 'Contacts'),
            'leads' => array('label' => 'LBL_LEADS_FILTERS', 'module' => 'Leads'),
            'users' => array('label' => 'LBL_USERS_FILTERS', 'module' => 'Users')
        );

        return $types;
    }

    /**
     * Load dynamic filters
     *
     * @static
     * @param string $prospect_list_id
     * @param string $related_type
     * @param bool $as_array
     * @return RowResult
     */
    static function loadFilters($prospect_list_id, $related_type=null, $as_array=false) {
        $lq = new ListQuery('dynamic_prospects');
        $clauses = array(
            "pl_id" => array(
                "value" => $prospect_list_id,
                "field" => "prospect_list_id",
            ),
        );
        if(isset($related_type)) {
            $clauses["related"] = array(
                "value" => $related_type,
                "field" => "related_type",
            );
        }

        $lq->addFilterClauses($clauses);

		if(isset($related_type))
			return $lq->runQuerySingle();
		$result = $lq->runQuery();
		if($as_array) {
			$filters = array();
			if (! $result->failed) {
				foreach($result->getRowIndexes() as $i) {
					$row = $result->getRow($i);
					$row['filters'] = unserialize($row['filters']);
					if(! is_array($row['filters']))
						$row['filters'] = array();
					$filters[$row['related_type']] = $row;
				}
			}
			return $filters;
		}
		return $result;
    }
    
    /**
     * Get where clauses
     *
     * @static
     * @param string $prospect_list_id
     * @return array
     */

    static function getWhereClauses($prospect_list_id) {
		return self::getListQueries($prospect_list_id, true);
    }

    /**
     * Get generated SQL where clauses by stored dynamic filters
     *
     * @static
     * @param string $prospect_list_id
     * @param bool $clauses_only
     * @return array
     */
    static function getListQueries($prospect_list_id, $clauses_only=false) {
		$module_filters = DynamicProspects::loadFilters($prospect_list_id, null, true);
        $dynamic_targets = DynamicProspects::getTypes();
        $ret = array();

        foreach ($dynamic_targets as $name => $params) {
        	if(isset($module_filters[$params['module']])) {
                $filters = $module_filters[$params['module']]['filters'];

                if(! empty($filters['filters_spec']) && ! empty($filters['filters_values'])) {
					$model_name = AppConfig::module_primary_bean($params['module']);
					$lq = new ListQuery($model_name);
					$lq->addPrimaryKey();
					$filter = new FilterForm($lq);

                    $values = $filters['filters_values'];
                    $spec = $filters['filters_spec'];
                    $filter->loadFilterLayout($spec);
                    $filter->loadArrayFilter($values);
                    $clauses = $filter->getFilterClauses();
					$lq->addFilterClauses($clauses);
                    if($clauses_only) {
						$links = $lq->getWhereClauseLinks();
						$joins = $lq->getQueryJoins(false);
						$ret[$params['module']] = array(
							'table_name' => $lq->getTableName(),
							'clauses' => $lq->getWhereClause(false, !!$joins),
							'joins' => $joins);
                    } else {
						$ret[$params['module']] = $lq;
					}
                }
            }
        }

        return $ret;
    }
}
