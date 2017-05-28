<?php

namespace mirocow\zendesk\common;

use Yii;
use yii\base\Model;

/**
 * Class baseModel
 * @author Mirocow <mr.mirocow@gmail.com>
 */
class baseModel extends Model
{

    public function load($data, $formName = null){
        $fields = array_intersect_key($data, $this->getAttributes());
        $this->setAttributes($fields);
    }

}