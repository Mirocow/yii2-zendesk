<?php

namespace mirocow\zendesk;

use mirocow\zendesk\common\baseModel;
use Yii;
use yii\web\UploadedFile;

/**
 * Class Comment
 * @author Derushev Aleksey <derushev.alexey@gmail.com>
 * @author Mirocow <mr.mirocow@gmail.com>
 * @package mirocow\zendesk
 * https://developer.zendesk.com/rest_api/docs/core/ticket_comments
 */
class Comment extends baseModel
{
    public $id;
    public $author_id;
    public $body;
    public $html_body;
    public $plain_body;
    public $public;
    public $attachments;
    public $audit_id;
    public $via;
    public $created_at;
    public $metadata;
    public $ticket_id;
    public $uploads;
    public $attachmentField = 'files';

    /**
     * @return array
     */
    public function rules()
    {
        return [
          [['id', 'created_at', 'author_id', 'audit_id', 'ticket_id', 'uploads'], 'safe'],
          [['metadata', 'via', 'attachments', 'uploads'], function($attribute) {
              return is_array($this->$attribute);
          }],
          [['body', 'html_body', 'plain_body', 'created_at'], 'string'],
          [['public', 'ticket_id'], 'integer'],
        ];
    }

    /**
     * @return mixed
     */
    public function getCreated()
    {
        return $this->created_at;
    }

    /**
     * @return mixed
     */
    public function getAuthor()
    {
        $data = Yii::$app->zendesk->get('/users/'.$this->author_id.'.json');
        if(empty($data['user'])){
            return null;
        }
        /** @var User $user */
        $user = new Yii::$app->zendesk->userClass();
        $user->load($data['user']);
        return $user;
    }

    /**
     *
     */
    public function setPrivate()
    {
        Yii::$app->zendesk->put('tickets/'.$this->ticket_id.'/comments/'.$this->id.'/make_private.json');
    }

    /**
     * @param bool $runValidation
     * @return mixed
     */
    public function save($runValidation = true)
    {
        if ($runValidation) {
            $this->validate();
        }

        $this->attachmentFiles();

        if ($this->id) {
            $text = $this->plain_body? $this->plain_body: $this->body;

            return Yii::$app->zendesk->put('tickets/'.$this->ticket_id.'/comments/'.$this->id.'/redact.json', [
              'body' => json_encode([
                'text ' => $text
              ])
            ]);
        }
        else {
            $result =  Yii::$app->zendesk->put('/tickets/'.$this->ticket_id.'.json', [
              'body' => json_encode([
                'ticket' => [
                  'comment' => $this->getAttributes()
                ]
              ])
            ]);
            $this->id = $result['ticket']['id'];

            return $this->id;
        }
    }

    /**
     * @return $this
     */
    public function attachmentFiles(){
        if(!empty($_FILES[$this->attachmentField]) && $_FILES[$this->attachmentField]['error'] <> 0) {
            foreach ($_FILES[$this->attachmentField]['error'] as $key => $error) {
                $this->attachmentFile($_FILES[$this->attachmentField]['tmp_name'][$key], $_FILES[$this->attachmentField]['name'][$key]);
            }
        }

        return $this;
    }

    /**
     * @param $filePath
     * @param string $name
     */
    protected function attachmentFile($filePath, $name = '')
    {
        if(empty($filePath)){
            return;
        }

        $uploadedFile = new UploadedFile(['tempName' => $filePath, 'name' => $name]);
        $zAttachment = new Attachment(['uploadedFile' => $uploadedFile]);
        $this->uploads[] = $zAttachment->save();
    }

}