<?php

namespace ShopwarePlugins\Connect\Components\ProductStream;

use ShopwarePlugins\Connect\Components\Struct;


class ProductStreamsAssignments extends Struct
{
    /**
     * @var array
     */
    public $assignments;

    /**
     * @param $articleId
     * @return array | null
     */
    public function getStreamsByArticleId($articleId)
    {
        if (isset($this->assignments[$articleId])) {
            return $this->assignments[$articleId];
        }

        return null;
    }

    /**
     * @return array
     */
    public function getArticleIds()
    {
        return array_keys($this->assignments);
    }

    /**
     * @return array
     */
    public function getArticleIdsWithoutStreams()
    {
        $articleIds = [];

        foreach ($this->getArticleIds() as $articleId) {
            if (empty($this->getStreamsByArticleId($articleId))) {
                $articleIds[] = $articleId;
            }
        }

        return $articleIds;
    }

}