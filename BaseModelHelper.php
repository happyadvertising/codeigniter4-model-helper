<?php
/**
 * @author denis303
 * @link http://denis303.com
 * @license MIT
 */
namespace denis303\codeigniter4;

abstract class BaseModelHelper
{

    public static function refreshEntity($modelClass, &$entity, &$error = null)
    {
        $primaryKey = static::getEntityPrimaryKey($modelClass, $entity, $error);

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

    public static function getEntityField($modelClass, $entity, $field)
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
            if (property_exists($entity, $primaryKey))
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

    public static function getEntityPrimaryKey($modelClass, $entity, &$error = null)
    {
        $model = new $modelClass;

        $primaryKey = $model->primaryKey;

        $return = static::getEntityField($modelClass, $entity);

        if (!$return === null)
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

        $saved = !$model->save($user);

        if (!$protect)
        {
            $model->protect(true);
        }

        if ($saved)
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

        return $model->getInsertID();
    }

    public static function createEntity($modelClass, $data = [], $save = true, $protect = true, &$error = null)
    {
        $model = new $modelClass;
        
        $entityClass = $model->returnType;

        $return = new $entityClass;

        foreach($data as $key => $value)
        {
            $return->$key = $value;
        }
    
        if ($save)
        {
            $id = static::saveEntity($modelClass, $entity, $protect, $error);
       
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

        $row = $model->where($where)->first();

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
                    $id = static::getEntityPrimaryKey($modelClass, $row, $error);

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

        $row = $model->where($where)->first();

        return $row;
    }

}