<?php

namespace mirocow\zendesk;

use Yii;
use yii\base\Model;
use yii\helpers\ArrayHelper;

/**
 * Class Search
 * @author Derushev Aleksey <derushev.alexey@gmail.com>
 * @author Mirocow <mr.mirocow@gmail.com>
 * @package mirocow\zendesk
 * @see https://developer.zendesk.com/rest_api/docs/core/search
 */
class Search extends Model
{
    const TYPE_TICKET = 'ticket';
    const TYPE_USER = 'user';
    const TYPE_ORGANIZATION = 'organization';
    const TYPE_GROUP = 'group';
    const TYPE_TOPIC = 'topic';

    const SORT_ASC = 'asc';
    const SORT_DESC = 'desc';

    public $query;
    public $type;
    public $sort_by;
    public $sort_order;

    public function rules()
    {
        return [
            [['type'], 'in', 'range' => [self::TYPE_TICKET, self::TYPE_USER, self::TYPE_ORGANIZATION, self::TYPE_GROUP, self::TYPE_TOPIC]],
            [['query'], function($attribute) {
                return is_array($this->$attribute);
            }],
            [['sort_by'], 'string'],
            [['sort_order'], 'in', 'range' => [self::SORT_ASC, self::SORT_DESC]],
        ];
    }

    /**
     * @return array|bool
     */
    public function find()
    {
        if ($this->validate()) {
            $query = [];
            if(is_array($this->query)) {
                $parts = [];
                foreach ($this->query as $fieldName => $part){
                    if(is_array($part)){
                        $parts[] = implode('', $part);
                    } else {
                        $parts[] = $fieldName . ':' . $part;
                    }
                }
                $query['query'] = urldecode(implode(' ', $parts));
            } else {
                $query['query'] = urldecode($this->query);
            }
            $query = ArrayHelper::merge($query, $this->getAttributes(['type', 'sort_by', 'sort_order']));
            $response = Yii::$app->zendesk->get('/search.json', ['query' => $query]);
            return isset($response['results']) ? $response['results'] : [];

        }
        else {
            return false;
        }
    }

    /**
     * Searches for the user
     * @return array
     */
    public function users()
    {
        $this->setAttributes(ArrayHelper::merge($this->getAttributes(), ['type' => 'user']));

        $zUsers = [];
        if ($results = $this->find()) {
            foreach ($results as $userData) {
                /** @var User $user */
                $user = new Yii::$app->zendesk->userClass();
                $user->load($userData);
                $zUsers[] = $user;
            }
        }

        return $zUsers;
    }

    /**
     * Searches for the ticket
     * @return array
     */
    public function tickets()
    {
        $this->setAttributes(ArrayHelper::merge($this->getAttributes(), ['type' => 'ticket']));

        $zTickets = [];
        if ($results = $this->find()) {
            foreach ($results as $ticketData) {
                /** @var Ticket $ticket */
                $ticket = new Yii::$app->zendesk->ticketClass;
                $ticket->load($ticketData);
                $zTickets[] = $ticket;
            }
        }

        return $zTickets;
    }
}