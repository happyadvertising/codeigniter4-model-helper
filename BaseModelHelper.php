<?php
/**
 * @author denis303
 * @link http://denis303.com
 * @license MIT
 */
namespace denis303\codeigniter4;

use CodeIgniter\Entity;

abstract class BaseModelHelper
{

    public static function findByPk($modelClass, $id)
    {
        $model = new $modelClass;

        if (!$id)
        {
            return null;
        }

        return $model->find($id);
    }

    public static function refreshEntity($modelClass, &$entity, &$error = null)
    {
        $primaryKey = static::entityPrimaryKey($modelClass, $entity, $error);

        if (!$primaryKey)
        {
            return false;
        }

        $model = new $modelClass;

        $entity = $model->find($primaryKey);

        if (!$entity)
        {
            $error = 'Entity not found.';
        
            return false;
        }
        
        return true;
    }

    public static function entityField($modelClass, $entity, $field)
    {
        $model = new $modelClass;

        if ($model->returnType == 'array')
        {
            if (array_key_exists($primaryKey, $entity))
            {
                return $entity[$field];
            }
        }
        else
        {
            if ($entity instanceof Entity)
            {
                return $entity->$field;
            }

            if (property_exists($entity, $model->primaryKey))
            {
                return $entity->$field;
            }
        }

        return null;
    }

    public static function setEntityField($modelClass, &$entity, $field, $value)
    {
        $model = new $modelClass;

        if ($model->returnType == 'array')
        {
            $entity[$field] = $value;
        }
        else
        {
            $entity->$field = $value;
        }
    }

    public static function entityPrimaryKey($modelClass, $entity, &$error = null)
    {
        $model = new $modelClass;

        $return = static::entityField($modelClass, $entity, $model->primaryKey);

        if (!$return)
        {
            $error = 'Primary key not defined.';

            return false;
        }

        return $return;
    }

    public static function saveEntity($modelClass, $entity, $protect = true, &$error = null)
    {
        $model = new $modelClass;

        if (!$protect)
        {
            $model->protect(false);
        }

        $id = $modelClass::entityPrimaryKey($entity);

        $saved = $model->save($entity);

        if (!$protect)
        {
            $model->protect(true);
        }

        if (!$saved)
        {
            $errors = $model->errors();

            if ($errors)
            {
                $error = array_shift($errors);
            }
            else
            {
                $error = 'Unknown error.';
            }
            
            return false;
        }

        if (!$id)
        {
            return $model->getInsertID();
        }

        return $id;
    }

    public static function createEntity($modelClass, $data = [], $save = false, $protect = true, &$error = null)
    {
        $model = new $modelClass;
        
        $entityClass = $model->returnType;

        if ($entityClass == 'array')
        {
            $return = [];

            foreach($data as $key => $value)
            {
                $return[$key] = $value;
            }
        }
        else
        {
            $return = new $entityClass;

            foreach($data as $key => $value)
            {
                $return->$key = $value;
            }            
        }
    
        if ($save)
        {
            $id = static::saveEntity($modelClass, $return, $protect, $error);
       
            if (!$id)
            {
                return false;
            }
        }

        return $return;
    }

    public static function getEntity($modelClass, array $where, bool $create = false, array $params = [], bool $update = false, &$error = null)
    {
        $model = new $modelClass;

        foreach($where as $key => $value) {

            $row = $model->where($key, $value)->find();
            if ($row)
            {
                if ($update)
                {
                    $updated = false;

                    foreach($params as $key => $value)
                    {
                        if ($row->$key != $value)
                        {
                            $row->$key = $value;

                            $updated = true;
                        }
                    }

                    if ($updated)
                    {
                        $id = static::entityPrimaryKey($modelClass, $row, $error);

                        if (!$id)
                        {
                            return false;
                        }

                        $model->protect(false);

                        $result = $model->update($id, $params);

                        $model->protect(true);
                    }
                }

                return $row;
            }
        }

        if (!$create)
        {
            return null;
        }

        foreach ($where as $key => $value)
        {
            $params[$key] = $value;
        }
    
        $model->protect(false);

        $result = $model->insert($params);

        $model->protect(true);

        if (!$result)
        {
            // nothing to do
        }

        foreach($where as $key => $value) {
            return $model->where($key, $value)->find();
            break;
        }
    }

}