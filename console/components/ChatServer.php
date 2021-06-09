<?php
namespace console\components; 

use yii;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use common\models\User;
use common\models\Device;
use console\components\Singleton;
use console\components\SingletonQueue;
use console\components\SingletonUser;
use console\components\SocketServer;
use console\controllers\Helper;

class ChatServer implements MessageComponentInterface
{
    public $users;
    var $singleton;
    var $singletonQueue;
    var $singletonUser;
    
    public function __construct()
    {
        $this->singleton = Singleton::getInstance();
        $this->singletonQueue = SingletonQueue::getInstance();
        $this->singletonUser = SingletonUser::getInstance();
        // $this->singleton = Yii::$container->get('Singleton');//Singleton::getInstance();
        // $this->singletonQueue = Yii::$container->get('SingletonQueue');//SingletonQueue::getInstance();
        // $this->singletonUser = Yii::$container->get('SingletonUser');//SingletonUser::getInstance();

        $this->singletonQueue->user = new \stdClass();
        $this->singletonQueue->user->user = [];
        $this->singletonQueue->user->socket = [];
    }
   
    public function onOpen(ConnectionInterface $conn)
    {
        echo "New connection! ({$conn->resourceId})\n";
        $this->singletonQueue->user->socket[$conn->resourceId] = new \stdClass();
        $this->singletonQueue->user->socket[$conn->resourceId]->socket = $conn;
        $this->singletonQueue->user->socket[$conn->resourceId]->user = null;
        $this->singletonQueue->user->socket[$conn->resourceId]->command = null;
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $data = json_decode($msg, true);
        
        if (is_null($data)) {
            echo "invalid data\n";
            return $from->close();
        }
        
        if($data['command'] == 'setValue') {
            echo 'setValue';
            print_r($msg);
            $client = User::find()->where(['auth_key' => $data['access_token']])->one();
            if (empty($client)) {
                $msg = new \stdClass();
                $msg->success = false;
                $msg->message = 'Вы не авторизованы!';
                $msg->device = $data['data']['device'];
                $msg->address = $data['data']['address'];
                $from->send(json_encode($msg));
                echo 'user not found'.PHP_EOL;
                return;
            }
            $this->singletonQueue->user->user[$client->id] = $from;
            $this->singletonQueue->user->socket[$from->resourceId]->user = $client;
            $this->singletonQueue->user->socket[$from->resourceId]->command = $data['data'];

            Yii::$app->db->open();
            $device = Device::find()->where(['id' => $data['data']['device']])->one();

            if (empty($device)) {
                $msg = new \stdClass();
                $msg->success = false;
                $msg->message = 'ТН не найден в базе!';
                $msg->device = $data['data']['device'];
                $msg->address = $data['data']['address'];
                $from->send(json_encode($msg));
                Yii::$app->db->close();
                echo 'device not found'.PHP_EOL;
                return;
            }

            Yii::$app->db->close();

            $fromDev = null;
            // $modems = Yii::$app->session['modems'];

            if (!array_key_exists($device->id, $this->singletonQueue->dev->dev)) {
                $msg = new \stdClass();
                $msg->success = false;
                $msg->message = 'ТН не найден в базе!';
                $msg->device = $data['data']['device'];
                $msg->address = $data['data']['address'];
                $from->send(json_encode($msg));
                echo 'modems not found'.PHP_EOL;
                return;
            }

            $fromDev = $this->singletonQueue->dev->dev[$device->id];

            if ($fromDev == null) {
                $msg = new \stdClass();
                $msg->success = false;
                $msg->message = 'ТН не найден в базе!';
                $msg->device = $data['data']['device'];
                $msg->address = $data['data']['address'];
                $from->send(json_encode($msg));
                echo 'socket not found'.PHP_EOL;
                return;
            }

            $obj = new \stdClass();
            $obj->device = $device;
            $obj->user = $client;
            $obj->socket = $fromDev;
            $obj->data = $data;
            $obj->command = 'write';
            $this->singleton->insert($obj, time());
            return;
        }
        
        // $client = User::find()->where(['auth_key' => $data['access_token']])->one();
        
        // if(empty($client)) {
        //     $msg = new \stdClass();
        //     $msg->success = FALSE;
        //     $msg->error = 'access denied';
        //     $from->send(json_encode($msg));
        //     return;
        // }
        
        // // print_r($data);
        
        // if($data['command'] == 'message') {
        //     // print_r($data);
        //     $dd = json_decode($data['data']);
        //     $user_id = $data['channel'];
        //     $type = $data['type'];
        //     $user2 = User::find()->where(['id' => $user_id])->one();
            
        //     if(empty($user2)) {
        //         $msg = new \stdClass();
        //         $msg->success = FALSE;
        //         $msg->error = 'chat not found';
        //         $from->send(json_encode($msg));
        //         return;
        //     }
            
        //     $message = new Messages();
        //     if ($type == 'text') {
        //         $message->from_id = $client->id;
        //         $message->to_id = $user2->id;
        //         $message->message = $dd->body;
        //         $message->type = 'text';
        //         $message->time = date('Y-m-d H:i:s', time());
        //     } else if ($type == 'image') {
        //         if (!file_exists(Yii::$app->basePath.'/../api/web/uploads/messages/')) {
        //             mkdir(Yii::$app->basePath.'/../api/web/uploads/messages/', 0777, true);
        //         }
        //         echo 'path='.Yii::$app->basePath.'/../api/web/uploads/messages/';
        //         $name = 'image-from-'.$client->id.'-to-'.$user2->id.'-'.date('Y-m-d_H:i:s', time());
        //         $output_file = Yii::$app->basePath.'/../api/web/uploads/messages/'.$name;
        //         $message->from_id = $client->id;
        //         $message->to_id = $user2->id;
        //         $message->message = $name;
        //         $message->type = 'image';
        //         $message->time = date('Y-m-d H:i:s', time());
        //         file_put_contents($output_file, base64_decode($dd->body));
        //     }
            
        //     if($message->save()) {
        //         $chat_id = $client->id.'_'.$user2->id;
                
        //         // print_r($this->chats);
        //         foreach ($this->chats as $key => $value) {
        //             echo 'key='.$key.' chat_id='.$chat_id.PHP_EOL;
        //         }
        //         if(array_key_exists($chat_id, $this->chats)) {
        //             $msg = new \stdClass();
        //             $msg->success = true;
        //             $msg->command = 'message';
        //             $obj = new \stdClass();
        //             $obj->_id = $message->id;
        //             $obj->text = $message->message;
        //             $obj->type = $message->type;
        //             $obj->old_id = $dd->old_id;
        //             $obj->createdAt = date('c', strtotime($message->time));
        //             $obj->time = $message->time;
        //             $obj->channel = $client->id;//$data['channel'];
        //             $user = new \stdClass();
        //             $user->_id = $client->id;
        //             $user->name = $client->fio;
        //             $user->avatar = $client->avatar;
        //             $obj->user = $user;
        //             $msg->message = $obj;
                    
        //             foreach ($this->chats as $key => $value) {
        //                 // echo 'key='.$key.' chat_id='.$user2->id.'_'.$client->id;
        //                 if ($key == $user2->id.'_'.$client->id) {
        //                     foreach ($this->chats[$key] as $key2 => $value2) {
        //                         // echo 'key='.$key2.PHP_EOL;
        //                         if ($key2 == 'socket') {
        //                             $value2->send(json_encode($msg));
        //                         }
        //                     }
        //                 }
        //             }

        //             $msg = new \stdClass();
        //             $msg->success = true;
        //             $msg->command = 'received';
        //             $obj = new \stdClass();
        //             $obj->_id = $message->id;
        //             $obj->text = $message->message;
        //             $obj->type = $message->type;
        //             $obj->old_id = $dd->old_id;
        //             $obj->createdAt = date('c', strtotime($message->time));
        //             $obj->time = $message->time;
        //             $obj->channel = $client->id;//$data['channel'];
        //             $user = new \stdClass();
        //             $user->_id = $client->id;
        //             $user->name = $client->fio;
        //             $user->avatar = $client->avatar;
        //             $obj->user = $user;
        //             $msg->message = $obj;

        //             $from->send(json_encode($msg));
        //         }
        //         $this->sendPush($client->fio, $message->message, $client, $user2);
        //     } else {
        //         $msg = new \stdClass();
        //         $msg->success = false;
        //         $msg->error = 'db error';
        //         $msg->command = 'not_received';
        //         $msg->message_id = $dd->id;
        //         $obj = new \stdClass();
        //         $obj->old_id = $dd->old_id;
        //         $obj->channel = $client->id;//$data['channel'];
        //         $msg->message = $obj;
        //         $from->send(json_encode($msg));
        //         return;
        //     }
        // } else if($data['command'] == 'enter') {
        //     print_r($data);
        //     $chat_id = $client->id.'_'.$data['channel'];
        //     if(!array_key_exists($chat_id, $this->chats)) {
        //         $this->chats[$chat_id] = new \stdClass();
        //         $this->chats[$chat_id]->socket = $from;
        //         $this->chats[$chat_id]->chat = $data['channel'];
        //     } else {
        //         $this->chats[$chat_id]->socket = $from;
        //         $this->chats[$chat_id]->chat = $data['channel'];
        //     }
        //     $uc = UserChat::find()->where(['user_id' => $client->id, 'chat_id' => $data['channel']])->one();
        //     if (empty($uc)) {
        //         $uc = new UserChat();
        //         $uc->chat_id = $data['channel'];
        //         $uc->user_id = $client->id;
        //         $uc->time = date('Y-m-d H:i:s', time());
        //     } else {
        //         $uc->time = date('Y-m-d H:i:s', time());
        //     }
        //     $uc->save();
        // } else if($data['command'] == 'exit') {
        //     print_r($data);
        //     $chat_id = $client->id.'_'.$data['channel'];
            
        //     // unset($this->chats[$chat_id]);
        //     $uc = UserChat::find()->where(['user_id' => $client->id, 'chat_id' => $data['channel']])->one();
        //     if (empty($uc)) {
        //         $uc = new UserChat();
        //         $uc->chat_id = $data['channel'];
        //         $uc->user_id = $client->id;
        //         $uc->time = date('Y-m-d H:i:s', time());
        //     } else {
        //         $uc->time = date('Y-m-d H:i:s', time());
        //     }
        //     $uc->save();
        // }
    }

    public function onClose(ConnectionInterface $conn)
    {
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }
}