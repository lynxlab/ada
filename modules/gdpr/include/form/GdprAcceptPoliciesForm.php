<?php

use Lynxlab\ADA\Services\NodeEditing\Utilities;

use Lynxlab\ADA\Module\GDPR\GdprAcceptPoliciesForm;

use Lynxlab\ADA\Module\GDPR\GdprAbstractForm;

use Lynxlab\ADA\Module\GDPR\GdprPolicy;

use Lynxlab\ADA\Main\Output\Output;

use Lynxlab\ADA\Main\Forms\lib\classes\FForm;

use function \translateFN;

// Trigger: ClassWithNameSpace. The class GdprAcceptPoliciesForm was declared with namespace Lynxlab\ADA\Module\GDPR. //

/**
 * @package     gdpr module
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2018, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

namespace Lynxlab\ADA\Module\GDPR;

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;
use function Lynxlab\ADA\Main\Utilities\ts2dFN;
use function Lynxlab\ADA\Main\Utilities\ts2tmFN;

/**
 * Class for the gpdr accept policies form
 *
 * @author giorgio
 */
class GdprAcceptPoliciesForm extends GdprAbstractForm
{
    public function __construct($formName = null, $action = null, $dataAr = [])
    {
        parent::__construct($formName, $action);
        if (!is_null($formName)) {
            $this->setId($formName);
            $this->setName($formName);
        }
        if (!is_null($action)) {
            $this->setAction($action);
        }
        self::addPolicies($this, $dataAr);
        $this->withSaveButton(translateFN('Salva'));
    }

    /**
     * Add policies contents and radio buttons to the passed form
     *
     * @param \FForm $formObj
     * @param array $dataAr
     * @return \FForm
     */
    public static function addPolicies($formObj, $dataAr)
    {
        if (is_array($dataAr) && count($dataAr) > 0) {
            if (array_key_exists('userId', $dataAr)) {
                $formObj->addHidden('userId')->withData($dataAr['userId']);
            }
            if (!array_key_exists('userAccepted', $dataAr)) {
                $dataAr['userAccepted'] = [];
            }
            $isRegistration = array_key_exists('isRegistration', $dataAr) && $dataAr['isRegistration'] === true;
            $acceptedPolicies = [];
            if (array_key_exists('policies', $dataAr)) {
                $firstElClass = 'active';
                /** @var GdprPolicy $policy */
                $accordion = CDOMElement::create('div', 'id:policies-accordion,class:ui fluid accordion');
                $accordion->addChild(CDOMElement::create('a', 'name:privacypolicies'));
                if (array_key_exists('extraclass', $dataAr)) {
                    $accordion->setAttribute('class', $accordion->getAttribute('class') . ' ' . $dataAr['extraclass']);
                }
                foreach ($dataAr['policies'] as $i => $policy) {
                    $acceptedPolicies[$policy->getPolicyContentId()] = false;
                    $title = CDOMElement::create('div', 'class:' . (($i == 0) ? $firstElClass . ' ' : '') . 'title');
                    $title->addChild(CDOMElement::create('i', 'class:dropdown icon'));
                    // policy title, left side
                    $spanTitle = CDOMElement::create('span', 'class:policy header');
                    $spanTitle->addChild(new CText($policy->getTitle()));
                    $title->addChild($spanTitle);
                    // policy accepted labels, right side
                    $labelColor = 'black';
                    if (array_key_exists($policy->getPolicyContentId(), $dataAr['userAccepted'])) {
                        $labelTitle = sprintf(
                            translateFN('Accettata in versione %d il %s, %s'),
                            $dataAr['userAccepted'][$policy->getPolicyContentId()]['acceptedVersion'],
                            ts2dFN($dataAr['userAccepted'][$policy->getPolicyContentId()]['lastmodTS']),
                            ts2tmFN($dataAr['userAccepted'][$policy->getPolicyContentId()]['lastmodTS'])
                        );
                        $isAccepted = true;
                    } else {
                        $isAccepted = false;
                        $labelTitle = '';
                    }

                    if ($isAccepted) {
                        if ($dataAr['userAccepted'][$policy->getPolicyContentId()]['acceptedVersion'] == $policy->getVersion()) {
                            $status = "ACCETTATA";
                            $labelColor = 'green';
                            $icon = 'ok sign';
                            $acceptedPolicies[$policy->getPolicyContentId()] = true;
                        } else {
                            $status = "NUOVA VERSIONE";
                            $labelColor = 'blue';
                            $icon = 'attention';
                        }
                    } elseif ($policy->getMandatory()) {
                        $status = $isRegistration ? "PRESTARE CONSENSO" : "NON ACCETTATA";
                        $labelColor = 'red';
                        $icon = 'warning';
                    }

                    $statusContainer = CDOMElement::create('div', 'class: policy status container');
                    $title->addChild($statusContainer);
                    if (isset($status)) {
                        $spanTitle = CDOMElement::create('span', 'class:policy status ui ' . $labelColor . ' label');
                        if (isset($labelTitle) && strlen($labelTitle) > 0) {
                            $spanTitle->setAttribute('title', $labelTitle);
                        } else {
                            $spanTitle->setAttribute('title', translateFN($status));
                        }
                        // $spanTitle->addChild(new CText(translateFN($status)));
                        $spanTitle->addChild(CDOMElement::create('i', 'class:ui icon ' . $icon));
                        unset($status);
                        $statusContainer->addChild($spanTitle);
                    }

                    if (!$policy->getMandatory() && !$isRegistration) {
                        $status = "FACOLTATIVA";
                        $labelColor = 'purple';
                        $icon = 'empty checkbox';
                        $spanTitle = CDOMElement::create('span', 'class:policy status ui ' . $labelColor . ' label');
                        if (isset($labelTitle) && strlen($labelTitle) > 0) {
                            $spanTitle->setAttribute('title', $labelTitle);
                        } else {
                            $spanTitle->setAttribute('title', translateFN($status));
                        }
                        // $spanTitle->addChild(new CText(translateFN($status)));
                        $spanTitle->addChild(CDOMElement::create('i', 'class:ui icon ' . $icon));
                        unset($status);
                        $statusContainer->addChild($spanTitle);
                    }
                    // policy content
                    $content = CDOMElement::create('div', 'class:' . (($i == 0) ? $firstElClass . ' ' : '') . 'content');
                    $textdiv = CDOMElement::create('div', 'class:policy text');
                    $textdiv->addChild(new CText($policy->getContent()));
                    $content->addChild($textdiv);

                    // accept and deny radio buttons
                    $fieldsContainer = CDOMElement::create('div', 'class:inline fields');
                    if ($isRegistration && $policy->getMandatory()) {
                        $fieldsContainer->setAttribute('data-mandatory-policy', '1');
                    }
                    $radios = [
                        1 => ['type' => 'accept', 'label' => 'Presto il consenso'],
                        0 => ['type' => 'deny', 'label' => 'Nego il consenso'],
                    ];
                    foreach ($radios as $value => $rData) {
                        $radioContainer = CDOMElement::create('div', 'class:field');
                        $radio = CDOMElement::create('radio', 'value:' . $value . ',name:acceptPolicy[' . $policy->getPolicyContentId() . '],id:' . $rData['type'] . '_' . $policy->getPolicyContentId());
                        if (
                            !$isRegistration && (($value === 1 && $acceptedPolicies[$policy->getPolicyContentId()] === true) ||
                            ($value === 0 && $acceptedPolicies[$policy->getPolicyContentId()] === false))
                        ) {
                            $radio->setAttribute('checked', 'checked');
                        }
                        $label = CDOMElement::create('label', 'class:' . $rData['type'] . ',for:' . $rData['type'] . '_' . $policy->getPolicyContentId());
                        $label->addChild(new CText(translateFN($rData['label'])));
                        $radioContainer->addChild($radio);
                        $radioContainer->addChild($label);
                        $fieldsContainer->addChild($radioContainer);
                    }
                    $content->addChild($fieldsContainer);
                    $accordion->addChild($title);
                    $accordion->addChild($content);
                }
                $formObj->addCDOM($accordion);

                $alert = CDOMElement::create('div', 'class:ui small modal,id:acceptPoliciesMSG');
                $aHeader = CDOMElement::create('div', 'class:header');
                $aHeader->addChild(new CText(translateFN('Attenzione')));
                $aContent = CDOMElement::create('div', 'class:content');
                $aContent->addChild(new CText('<i class="large warning icon"></i>' . translateFN('Per ' . ($isRegistration ? 'registrarsi' : 'continuare') . ', è necessario prestare il consenso a tutte le politiche di gestione dei dati personali')));

                $aActions = CDOMElement::create('div', 'class:actions');
                $button = CDOMElement::create('div', 'class:ui red button');
                $button->addChild(new CText(translateFN('OK')));
                $aActions->addChild($button);

                $alert->addChild($aHeader);
                $alert->addChild($aContent);
                $alert->addChild($aActions);

                $formObj->addCDOM($alert);
            }
        }
        return $formObj;
    }

    /**
     * Adds a save button with the passed label
     *
     * @param string $label
     * @return \Lynxlab\ADA\Module\GDPR\GdprAcceptPoliciesForm
     */
    public function withSaveButton($label)
    {
        // save button
        $saveBtn = $this->addButton('savePolicies', $label);
        $saveBtn->setAttribute('class', 'ui large green button');
        return $this;
    }
}
