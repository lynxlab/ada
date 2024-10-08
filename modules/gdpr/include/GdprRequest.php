<?php

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
use Lynxlab\ADA\Main\AMA\AMACommonDataHandler;
use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\User\ADALoggableUser;
use Lynxlab\ADA\Module\GDPR\AMAGdprDataHandler;
use Lynxlab\ADA\Module\GDPR\GdprActions;
use Lynxlab\ADA\Module\GDPR\GdprAPI;
use Lynxlab\ADA\Module\GDPR\GdprBase;
use Lynxlab\ADA\Module\GDPR\GdprPolicy;
use Lynxlab\ADA\Module\GDPR\GdprRequestType;
use Ramsey\Uuid\Uuid;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * Class for a GDPR request
 *
 * @author giorgio
 */
class GdprRequest extends GdprBase
{
    /**
     * table name for this class
     *
     * @var string
     */
    public const TABLE =  AMAGdprDataHandler::PREFIX . 'requests';

    public const ACTIONBUTTONLABEL = 'evadi';
    public const CLOSEBUTTONLABEL = 'chiudi';

    protected $uuid;
    protected $generatedBy;
    protected $generatedTs;
    protected $confirmedTs;
    protected $closedBy;
    protected $closedTs;
    protected $type;
    protected $content;
    protected $selfOpened;

    /**
     * constructor will always generate a new uuid for the object
     *
     * @param array $data
     * @param AMAGdprDataHandler|GDPRApi $$dbToUse
     */
    public function __construct($data = [], $dbToUse = null)
    {
        if (is_null($dbToUse)) {
            $dbToUse = new GdprAPI();
        }
        if (is_null($this->fromArray($data, $dbToUse)->getUuid())) {
            $this->setUuid(Uuid::uuid4()->toString());
        }
    }

    /**
     * override fromArray method to handle type that must be
     * an instance of GdprRequestType
     *
     * {@inheritDoc}
     * @see \Lynxlab\ADA\Module\GDPR\GdprBase::fromArray()
     *
     * @param array $data
     * @param AMAGdprDataHandler|GDPRApi $$dbToUse
     *
     * @return \Lynxlab\ADA\Module\GDPR\GdprRequest
     */

    public function fromArray($data = [], $dbToUse = null)
    {
        if (array_key_exists('type', $data) && intval($data['type']) > 0) {
            $result = $dbToUse->findBy('GdprRequestType', ['id' => intval($data['type'])]);
            if (count($result) > 0) {
                $this->setType(reset($result));
            }
            unset($data['type']);
        }
        return parent::fromArray($data, $dbToUse);
    }

    /**
     * Gets the action button object
     *
     * @param boolean $isClose
     * @return NULL|\CBaseElement
     */
    public function getActionButton($isClose = false)
    {
        $button = CDOMElement::create('button', 'type:button,class:ui tiny button');
        $button->addChild(new CText(translateFN($isClose ? self::CLOSEBUTTONLABEL : self::ACTIONBUTTONLABEL)));
        $button->setAttribute('data-requestuuid', $this->getUuid());
        $button->setAttribute('data-requesttype', $this->getType()->getType());
        $button->setAttribute('data-isclose', $isClose ? 1 : 0);
        if ($isClose) {
            $button->setAttribute('class', $button->getAttribute('class') . ' red');
        } else {
            if ($this->getType()->confirmBeforeHandle()) {
                $button->setAttribute('data-confirmhandle', 1);
            }
        }
        return $button;
    }

    /**
     * Close a request
     *
     * @param integer $closedBy
     * @return \Lynxlab\ADA\Module\GDPR\GdprRequest
     */
    public function close($closedBy = null)
    {
        (new GdprAPI())->closeRequest($this, $closedBy);
        return $this;
    }

    /**
     * Confirm a request
     *
     * @return \Lynxlab\ADA\Module\GDPR\GdprRequest
     */
    public function confirm()
    {
        (new GdprAPI())->confirmRequest($this);
        return $this;
    }

    /**
     * Performs the action on the request
     *
     * @throws GdprException
     */
    public function handle()
    {
        if ($this->getType()->getType() == GdprRequestType::EDIT) {
            $this->redirecturl = $_SESSION['sess_userObj']->getEditProfilePage();
            $this->redirectlabel = translateFN('Modifica i tuoi dati');
            if (GdprActions::canDo(GdprActions::ACCESS_ALL_REQUESTS)) {
                $this->redirecturl = str_replace('edit_switcher.php', 'edit_user.php', $this->redirecturl) . '?id_user=' . $this->getGeneratedBy();
                $this->close();
            }
        } elseif ($this->getType()->getType() == GdprRequestType::ACCESS) {
            $this->redirectlabel = translateFN('Scarica PDF con i tuoi dati');
            $this->redirecturl = HTTP_ROOT_DIR . '/switcher/view_user.php?pdfExport=1&id_user=' . $this->getGeneratedBy();
            $this->reloaddata = true;
            $this->close();
        } elseif (in_array($this->getType()->getType(), [GdprRequestType::ONHOLD, GdprRequestType::DELETE])) {
            $selfUserErrMessages = [
                GdprRequestType::ONHOLD => 'Impossibile disabilitare te stesso',
                GdprRequestType::DELETE => 'Impossibile cancellare te stesso',
            ];
            $this->reloaddata = true;
            /**
             * Check on user type to prevent multiport to do its error handling if no user found
             */
            if (!AMADB::isError(AMACommonDataHandler::getInstance()->getUserType($this->getGeneratedBy()))) {
                $targetUser = MultiPort::findUser(intval($this->getGeneratedBy()));
                if ($targetUser instanceof ADALoggableUser) {
                    if ($_SESSION['sess_userObj']->getId() != $targetUser->getId()) {
                        if ($this->getType()->getType() == GdprRequestType::ONHOLD) {
                            $targetUser->setStatus(ADA_STATUS_PRESUBSCRIBED);
                        } elseif ($this->getType()->getType() == GdprRequestType::DELETE) {
                            if ($targetUser->getStatus() == ADA_STATUS_PRESUBSCRIBED) {
                                $targetUser->anonymize();
                            } else {
                                throw new GdprException(translateFN("Prima di cancellare un utente, deve essere disattivato"));
                            }
                        }
                        MultiPort::setUser($targetUser, [], true);
                        $this->close();
                    } else {
                        throw new GdprException(translateFN($selfUserErrMessages[$this->getType()->getType()]));
                    }
                }
            } else {
                throw new GdprException(translateFN("Impossibile trovare l'utente che ha fatto la richiesta"));
            }
        } elseif ($this->getType()->getType() == GdprRequestType::OPPOSITION) {
            $this->redirectlabel = translateFN('Modifica le impostaioni di privacy');
            $this->redirecturl = MODULES_GDPR_HTTP . '/' . GdprPolicy::ACCEPTPOLICIESPAGE;
            $this->reloaddata = true;
            $this->close();
        } else {
            throw new GdprException('AZIONE NON IMPLEMENTATA');
        }
        return $this;
    }

    /**
     * Method that performs additional actions on the request before it's been saved
     * usually called by the datahandler save methods just before saving
     *
     * @param bool $isUpdate
     * @return bool false to not actually save the request and NOT call afterSave
     */
    public function beforeSave($isUpdate)
    {
        return true;
    }

    /**
     * Method that performs additional actions on the request after it's been saved
     * usually called by the datahandler save methods just before returning
     *
     * @param bool $isUpdate
     * @return \Lynxlab\ADA\Module\GDPR\GdprRequest
     */
    public function afterSave($isUpdate)
    {
        if (!$isUpdate) {
            if (GdprActions::canDo($this->getType()->getLinkedAction(), $this)) {
                if (
                    in_array($this->getType()->getType(), [
                    GdprRequestType::EDIT, GdprRequestType::ACCESS, GdprRequestType::OPPOSITION,
                    ])
                ) {
                    return $this->handle()->close();
                }
            }
        }
        return $this;
    }

    /**
     * Gets the header array for the requests html table
     *
     * @param bool $showall true if action column must be shown
     * @return array
     */
    public static function getTableHeader($showall = false)
    {
        $headerArr = [
            'Numero pratica',
            'Tipo',
            'Creata il',
            'Chiusa il',
        ];
        if ($showall) {
            $headerArr = array_merge($headerArr, ['Testo/Note', 'Azioni']);
        }

        return array_map(fn ($el) => ucwords(strtolower(translateFN($el))), $headerArr);
    }

    /**
     * @return mixed
     */
    public function getUuid()
    {
        return $this->uuid;
    }

    /**
     * @return mixed
     */
    public function getGeneratedBy()
    {
        return $this->generatedBy;
    }

    /**
     * @return mixed
     */
    public function getGeneratedTs()
    {
        return $this->generatedTs;
    }

    /**
     * @return mixed
     */
    public function getConfirmedTs()
    {
        return $this->confirmedTs;
    }

    /**
     * @return mixed
     */
    public function getClosedBy()
    {
        return $this->closedBy;
    }

    /**
     * @return mixed
     */
    public function getClosedTs()
    {
        return $this->closedTs;
    }

    /**
     * @return GdprRequestType
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return mixed
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @return mixed
     */
    public function getSelfOpened()
    {
        return $this->selfOpened;
    }

    /**
     * @param mixed $uuid
     */
    public function setUuid($uuid)
    {
        $this->uuid = $uuid;
        return $this;
    }

    /**
     * @param mixed $generatedBy
     */
    public function setGeneratedBy($generatedBy)
    {
        $this->generatedBy = $generatedBy;
        return $this;
    }

    /**
     * @param mixed $generatedTs
     */
    public function setGeneratedTs($generatedTs)
    {
        $this->generatedTs = $generatedTs;
        return $this;
    }

    /**
     * @param mixed $confirmedTs
     */
    public function setConfirmedTs($confirmedTs)
    {
        $this->confirmedTs = $confirmedTs;
        return $this;
    }

    /**
     * @param mixed $closedBy
     */
    public function setClosedBy($closedBy)
    {
        $this->closedBy = $closedBy;
        return $this;
    }

    /**
     * @param mixed $closedTs
     */
    public function setClosedTs($closedTs)
    {
        $this->closedTs = $closedTs;
        return $this;
    }

    /**
     * @param GdprRequestType $type
     */
    public function setType(GdprRequestType $type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @param mixed $content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * @param mixed $selfOpened
     */
    public function setSelfOpened($selfOpened)
    {
        $this->selfOpened = $selfOpened;
        return $this;
    }
}
