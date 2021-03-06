<?php
/**
 * Created by PhpStorm.
 * User: Simon
 * Date: 14.09.14
 * Time: 17:40
 */

namespace Bryah\Hermes\Models;



class MessageState extends EloquentBase {

    public static $states = array(
        0 => 'unread',
        1 => 'read',
        2 => 'own',
        3 => 'deleted'
    );

    public static function indexOf($key){
        $states = static::$states;

        $indexResult = array_search($key, $states);

        if($indexResult === FALSE)
        	throw new \Exception('Message state ' . $key . ' is unknown.');
        else
        	return $indexResult;
    }

    protected $table = 'message_states';

    public function message()
    {
        return $this->belongsTo(EloquentBase::modelPath('Message'));
    }

    public function user()
    {
        $userClass = EloquentBase::userClass();
        return $this->belongsTo($userClass);
    }



}
