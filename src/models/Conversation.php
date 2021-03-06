<?php
/**
 * Created by PhpStorm.
 * User: Simon
 * Date: 14.09.14
 * Time: 17:40
 */

namespace Bryah\Hermes\Models;



use Auth;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Bryah\Hermes\Classes\MessageGroup;

class Conversation extends EloquentBase {

    protected $table = 'conversations';


    /**
     * All messages that already are in this conversation
     *
     * @return Collection
     */
    public function messages()
    {
        return $this->hasMany(static::modelPath('Message'))->orderBy('updated_at', 'desc')->with('user');
    }


    /**
     * The users that are talking to each other
     *
     * @return Collection
     */
    public function users()
    {
        $userClass = EloquentBase::userClass();

        return $this->belongsToMany($userClass, parent::tableName('conversation_user'))->withTimestamps();
    }

    /**
     * Get all the users that the current user is talking to in this conversation
     *
     * @return Collection
     */
    public function otherUsers(){
        if(!Auth::check()) return $this->users;

        return $this->users
            ->filter(function($user){ return $user->id != Auth::user()->id; });
    }


    /**
     * Add a new message to this conversation.
     * Leave the user to null and the currently logged in user will send the message.
     *
     * @param string $content
     * @param Model $user
     * @return Message
     * @throws \Exception
     */
    public function addMessage($content, Model $user = null){
        $user = $this->getUser($user);


        if(!$this->canWrite($user)){
            return false;
        }

        $newMessage = new Message();

        $newMessage->user_id = $user->id;
        $newMessage->conversation_id = $this->id;
        $newMessage->content = $content;

        $newMessage->save();

        foreach($this->users as $convUser){
            $newMessageState = new MessageState();
            $newMessageState->user_id = $convUser->id;
            $newMessageState->message_id = $newMessage->id;
            $newMessageState->state = MessageState::indexOf('unread');

            if($user->id == $convUser->id)
                $newMessageState->state = MessageState::indexOf('own');

            $newMessageState->save();
        }

        return $newMessage;


    }

    /**
     * Add a user to the party.
     *
     * @param Model $user
     */
    public function addUser(Model $user){
        $this->users()->attach($user->id);
        $this->touch();
    }

    /**
     * @param Model $user
     * @return bool
     * @throws \Exception
     */
    public function canWrite(Model $user){
        $user = $this->getUser($user);

        foreach($this->users as $convUser){
            if($convUser->id == $user->id) return true;
        }

        return false;

    }

    public function latestMessage(){
        return $this->messages()->first();
    }

    public function unreadMessages(){
        //TODO: Do this using eloquent


        $unreadMessages = array();
        foreach($this->messages as $message){
            if($message->isUnread()){
                $unreadMessages[] = $message;
            }
        }



        return $unreadMessages;
    }

    public function isUnread(){
        return count($this->unreadMessages()) > 0;
    }


    public function doRead(){
        foreach($this->unreadMessages() as $unreadMessage){
            $unreadMessage->doRead();
        }

        return true;
    }

    public function buildGroups(){
        return MessageGroup::buildGroups($this);
    }


}
