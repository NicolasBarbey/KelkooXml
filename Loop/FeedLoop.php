<?php

namespace KelkooXml\Loop;

use KelkooXml\Model\KelkooxmlFeed;
use KelkooXml\Model\KelkooxmlFeedQuery;
use Thelia\Core\Template\Element\BaseLoop;
use Thelia\Core\Template\Element\LoopResult;
use Thelia\Core\Template\Element\LoopResultRow;
use Thelia\Core\Template\Element\PropelSearchLoopInterface;
use Thelia\Core\Template\Loop\Argument\ArgumentCollection;

class FeedLoop extends BaseLoop implements PropelSearchLoopInterface
{
    public function getArgDefinitions()
    {
        return new ArgumentCollection();
    }

    public function buildModelCriteria()
    {
        $query = KelkooxmlFeedQuery::create();

        return $query;
    }


    public function parseResults(LoopResult $loopResult)
    {
        /** @var KelkooxmlFeed $data */
        foreach ($loopResult->getResultDataCollection() as $data) {
            $loopResultRow = new LoopResultRow();
            $loopResultRow->set("ID", $data->getId());
            $loopResultRow->set("LABEL", $data->getLabel());
            $loopResultRow->set("LANG_ID", $data->getLangId());
            $loopResultRow->set("CURRENCY_ID", $data->getCurrencyId());
            $loopResultRow->set("COUNTRY_ID", $data->getCountryId());

            $loopResult->addRow($loopResultRow);
        }

        return $loopResult;
    }
}
