<?php

/**
 * Base Management Class
 *
 * @package         classbudget module
 * @author          Giorgio Consorti <g.consorti@lynxlab.com>
 * @copyright       Copyright (c) 2025, Lynx s.r.l.
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link            classbudget
 * @version         0.1
 */

namespace Lynxlab\ADA\Module\Classbudget;

use DateTimeImmutable;
use FatturaElettronicaPhp\FatturaElettronica\Address;
use FatturaElettronicaPhp\FatturaElettronica\Customer;
use FatturaElettronicaPhp\FatturaElettronica\DigitalDocument;
use FatturaElettronicaPhp\FatturaElettronica\DigitalDocumentInstance;
use FatturaElettronicaPhp\FatturaElettronica\Enums\DocumentType;
use FatturaElettronicaPhp\FatturaElettronica\Enums\PaymentMethod;
use FatturaElettronicaPhp\FatturaElettronica\Enums\PaymentTerm;
use FatturaElettronicaPhp\FatturaElettronica\Enums\TaxRegime;
use FatturaElettronicaPhp\FatturaElettronica\Enums\TransmissionFormat;
use FatturaElettronicaPhp\FatturaElettronica\Enums\VatEligibility;
use FatturaElettronicaPhp\FatturaElettronica\Enums\VatNature;
use FatturaElettronicaPhp\FatturaElettronica\Line;
use FatturaElettronicaPhp\FatturaElettronica\PaymentDetails;
use FatturaElettronicaPhp\FatturaElettronica\PaymentInfo;
use FatturaElettronicaPhp\FatturaElettronica\Product;
use FatturaElettronicaPhp\FatturaElettronica\Supplier;
use FatturaElettronicaPhp\FatturaElettronica\Total;

class InvoiceManagement
{
    private const ADDER = 'add';
    private const SETTER = 'set';
    public const SEPARATOR = '::';

    protected $invoice;
    protected $lines = [];

    public function __construct(
        public $supplier = [],
        public $customer = [],
        protected $type = 'xml',
    ) {
        if ($type == 'xml') {
            $this->invoice = new DigitalDocument();
        }
        $this->setSupplier($supplier);
        $this->setCustomer($customer);
    }

    public function setInvoiceLines($invoiceData)
    {
        $this->lines = [];
        $lineNum = 0;
        foreach ($invoiceData['events'] ?? [] as $events) {
            foreach ($events as $what => $event) {
                foreach ($event as $evData) {
                    $code = $invoiceData['instances'][$evData['id_istanza_corso']][0]['course']['code'];
                    $description = implode(
                        ' - ',
                        [
                            $invoiceData['instances'][$evData['id_istanza_corso']][0]['course']['title'],
                            $invoiceData['instances'][$evData['id_istanza_corso']][0]['title'],
                        ]
                    );
                    $unitPrice = $evData['cost_rate'] ?? $evData['default_rate'];  // controllare bene!!
                    if ($what == 'tutor') {
                        $description .= ', costo tutor: ' . $evData['name'] . ' ' . $evData['lastname'];
                    } elseif ($what == 'classroom') {
                        $description .= ', costo aula: ' . $evData['venuename'] . ' ' . $evData['roomname'];
                    }

                    if ($unitPrice > 0) {
                        $this->lines[] = [
                            Product::class . '::CodeType' => 'ADA',  // PRENDERE DA COSTANTE!!
                            Product::class . '::Code' => $code,
                            'Number' => ++$lineNum,
                            'VatNature' => VatNature::N4(),
                            'TaxPercentage' => 0,
                            'Unit' => 'Ore', // UN GIORNO VERRÀ DA evData
                            'Description' => $description,
                            'Quantity' => ($evData['duration'] / 3600),
                            'UnitPrice' => $unitPrice,
                            'Total' => $unitPrice * ($evData['duration'] / 3600),
                        ];
                    }
                }
            }
        }
        if (!empty($this->lines)) {
            $this->lines[] = [
                'Number' => ++$lineNum,
                'Description' => 'Prestazioni sanitarie mese Gennaio 2025. La presente fattura costituisce titolo per la deducibilità fiscale e il rimborso assicurativo della spesa ESCLUSIVAMENTE se quietanzata in calce con timbro e firma di Wecare. Bollo di  2,00 a carico Wecare',
                'UnitPrice' => 0,
                'Total' => 0,
                'TaxPercentage' => 0,
                'VatNature' => VatNature::N4(),
            ];
        }
    }

    public function asXML()
    {
        if ($this->type != 'xml') {
            throw new ClassBudgetException(__METHOD__ . ' called but invoice type is not XML');
        }
        $finalized = $this->finalize();
        $this->invoice = new DigitalDocument();
        $doc = dom_import_simplexml($finalized->serialize())->ownerDocument;
        $doc->formatOutput = true;
        return $doc->saveXML();
    }

    /**
     * Get the value of invoice
     */
    public function getInvoice()
    {
        return $this->invoice;
    }

    /**
     * Set the value of invoice
     */
    public function setInvoice($invoice): self
    {
        $this->invoice = $invoice;

        return $this;
    }

    /**
     * Get the value of supplier
     */
    public function getSupplier()
    {
        return $this->supplier;
    }

    /**
     * Set the value of supplier
     */
    public function setSupplier($supplier): self
    {

        $this->supplier = $supplier;
        if ($this->type == 'xml') {
            /**
             * Sezione CedentePrestatore
             */
            $supplier = new Supplier();
            $supplierAddress = new Address();
            $supplier
                ->setTaxRegime(TaxRegime::RF01())
                ->setAddress(
                    $supplierAddress
                        ->setStreet(strtoupper($this->supplier['indirizzo']))
                        ->setZip(strtoupper($this->supplier['cap']))
                        ->setCity(strtoupper($this->supplier['citta']))
                        ->setCountryCode(strtoupper($this->supplier['nazione']))
                        ->setState(strtoupper($this->supplier['provincia']))
                )
                ->setFiscalCode(strtoupper($this->supplier['codice_fiscale']))
                ->setOrganization(strtoupper($this->supplier['ragione_sociale']))
                ->setCountryCode(strtoupper($this->supplier['nazione']))
                ->setVatNumber(strtoupper($this->supplier['partita_iva']))
            ;
            $this->invoice->setCountryCode(strtoupper($this->supplier['nazione']))->setSupplier($supplier);
        }

        return $this;
    }

    /**
     * Get the value of customer
     */
    public function getCustomer()
    {
        return $this->customer;
    }

    /**
     * Set the value of customer
     */
    public function setCustomer($customer): self
    {
        $this->customer = $customer;
        if ($this->type == 'xml') {
            /**
             * Sezione CessionarioCommittente
             */
            $customer = new Customer();
            $address = new Address();
            $customer
                ->setAddress(
                    $address
                        ->setStreet(strtoupper($this->customer['indirizzo'] ?? ''))
                        ->setZip(strtoupper($this->customer['cap'] ?? ''))
                        ->setCity(strtoupper($this->customer['citta'] ?? ''))
                        ->setCountryCode(strtoupper($this->customer['nazione'] ?? ''))
                        ->setState(strtoupper($this->customer['provincia'] ?? ''))
                )
                ->setFiscalCode(strtoupper($this->customer['codice_fiscale'] ?? ''))
                ->setName(strtoupper($this->customer['nome'] ?? ''))
                ->setSurname(strtoupper($this->customer['cognome'] ?? ''))
            ;
            $this->invoice->setCustomer($customer);
        }

        return $this;
    }

    /**
     * Set the value of type
     */
    public function setType($type): self
    {
        $this->type = $type;

        return $this;
    }

    private function finalize()
    {
        /**
         * Sezione DatiTrasmissione
         */
        $this->invoice
            ->setTransmissionFormat(TransmissionFormat::FPR12())
            ->setCountryCode($this->supplier['nazione'] ?? '')
            ->setCustomerSdiCode('0000000')
            ->setSenderVatId('01533080675')
            ->setSendingId('00000') // progressivo invio
        ;

        $docDate = new DateTimeImmutable();
        if ($this->type == 'xml') {
            $docinstance = new DigitalDocumentInstance();
            /**
             * Sezione DatiGenerali
             */
            $docinstance
                ->setDocumentType(DocumentType::TD06())
                ->setCurrency('EUR')
                ->setDocumentDate($docDate->format('Y-m-d'))
                ->setDocumentNumber('153/2025/PAR')
                ->setDocumentTotal(0)
            ;
            /**
             * Sezione DatiBeniServizi
             */
            foreach ($this->lines as $line) {
                $lineObj = static::buildLineObj($line);
                $docinstance->setDocumentTotal(
                    $docinstance->getDocumentTotal() + $lineObj->getTotal()
                );
                $docinstance->addLine($lineObj);
            }
            /**
             * sezione DatiRiepilogo
             */
            $docinstance->addTotal(
                (new Total())
                    ->setTaxPercentage(0)
                    ->setVatNature(VatNature::N4())
                    ->setTotal($docinstance->getDocumentTotal())
                    ->setTaxAmount(0)
                    ->setTaxType(VatEligibility::I())
            );
            /**
             * sezione DatiPagamento
             */
            $docinstance->addPaymentInformations(
                (new PaymentInfo())
                    ->setTerms(PaymentTerm::TP02())
                    ->addDetails(
                        (new PaymentDetails())
                            ->setMethod(PaymentMethod::MP05())
                            ->setIban($this->supplier['iban'])
                            ->setAmount($docinstance->getDocumentTotal())
                            ->setDueDate($docDate->format('Y-m-d'))
                    )
            );
            return $this->invoice->addDigitalDocumentInstance($docinstance);
        }
    }

    private static function buildLineObj($line): Line
    {
        $lineObj = new Line();
        $subObjs = [];
        foreach ($line as $key => $val) {
            if (str_contains($key, self::SEPARATOR)) {
                [$class, $prop] = explode(self::SEPARATOR, $key);
                if (class_exists($class)) {
                    if (!array_key_exists($class, $subObjs)) {
                        $subObjs[$class] = new $class();
                        $adder = self::ADDER . array_reverse(explode('\\', $class))[0];
                        $lineObj->{$adder}($subObjs[$class]);
                    }
                    $subObjs[$class]->{self::SETTER . $prop}($val);
                }
            } else {
                $lineObj->{self::SETTER . $key}($val);
            }
        }
        return $lineObj;
    }
}
