<?php

namespace SmartDato\GlsItaly\LabelService;

use SmartDato\GlsItaly\Data\Credentials;
use SmartDato\GlsItaly\Data\ParcelData;
use SmartDato\GlsItaly\Data\RecipientData;
use SmartDato\GlsItaly\Data\ShipmentData;
use SmartDato\GlsItaly\Enums\LabelFormat;
use SmartDato\GlsItaly\Exceptions\ValidationException;
use SmartDato\GlsItaly\LabelService\Requests\AddParcelRequest;
use SmartDato\GlsItaly\LabelService\Results\AddParcelResult;

class ShipmentBuilder
{
    protected ?string $contractCode = null;

    protected ?RecipientData $recipient = null;

    protected ?ParcelData $parcel = null;

    protected float $cashOnDelivery = 0.0;

    protected ?string $codCollectionMode = null;

    protected string $note = '';

    protected string $additionalNote = '';

    protected string $clientReference = '';

    protected string $bda = '';

    /** @var array<int, string> */
    protected array $services = [];

    protected LabelFormat $labelFormat = LabelFormat::Pdf;

    protected ?int $progressiveCounter = null;

    protected ?string $deliveryTimeNote = null;

    public function __construct(
        protected readonly LabelServiceConnector $connector,
        protected ?Credentials $credentials = null,
    ) {}

    public function credentials(Credentials $credentials): self
    {
        $this->credentials = $credentials;

        return $this;
    }

    public function contractCode(string $contractCode): self
    {
        $this->contractCode = $contractCode;

        return $this;
    }

    public function recipient(RecipientData $recipient): self
    {
        $this->recipient = $recipient;

        return $this;
    }

    public function parcel(ParcelData $parcel): self
    {
        $this->parcel = $parcel;

        return $this;
    }

    public function cashOnDelivery(float $amount, ?string $collectionMode = null): self
    {
        $this->cashOnDelivery = $amount;
        $this->codCollectionMode = $collectionMode;

        return $this;
    }

    public function note(string $note): self
    {
        $this->note = $note;

        return $this;
    }

    public function additionalNote(string $additionalNote): self
    {
        $this->additionalNote = $additionalNote;

        return $this;
    }

    public function clientReference(string $clientReference): self
    {
        $this->clientReference = $clientReference;

        return $this;
    }

    public function bda(string $bda): self
    {
        $this->bda = $bda;

        return $this;
    }

    /**
     * @param  array<int, string>  $serviceCodes  two-character GLS accessory service codes
     */
    public function services(array $serviceCodes): self
    {
        $this->services = $serviceCodes;

        return $this;
    }

    public function labelFormat(LabelFormat $labelFormat): self
    {
        $this->labelFormat = $labelFormat;

        return $this;
    }

    public function progressiveCounter(int $progressiveCounter): self
    {
        $this->progressiveCounter = $progressiveCounter;

        return $this;
    }

    public function deliveryTimeNote(string $deliveryTimeNote): self
    {
        $this->deliveryTimeNote = $deliveryTimeNote;

        return $this;
    }

    /**
     * @throws ValidationException
     */
    public function toData(): ShipmentData
    {
        $this->validate();

        return new ShipmentData(
            contractCode: $this->contractCode,
            recipient: $this->recipient,
            parcel: $this->parcel,
            cashOnDelivery: $this->cashOnDelivery,
            codCollectionMode: $this->codCollectionMode,
            note: $this->note,
            additionalNote: $this->additionalNote,
            clientReference: $this->clientReference,
            bda: $this->bda,
            services: $this->services,
            labelFormat: $this->labelFormat,
            progressiveCounter: $this->progressiveCounter,
            deliveryTimeNote: $this->deliveryTimeNote,
        );
    }

    /**
     * @throws ValidationException
     */
    public function create(): AddParcelResult
    {
        if ($this->credentials === null) {
            throw new ValidationException('Credentials are required, call GlsItaly::withCredentials() or credentials() before create().');
        }

        $response = $this->connector->call(new AddParcelRequest($this->credentials, $this->toData()));

        return AddParcelResult::fromResponse($response);
    }

    protected function validate(): void
    {
        if ($this->contractCode === null) {
            throw new ValidationException('A contract code is required, call contractCode() before create().');
        }

        if ($this->recipient === null) {
            throw new ValidationException('A recipient is required, call recipient() before create().');
        }

        if ($this->parcel === null) {
            throw new ValidationException('A parcel is required, call parcel() before create().');
        }
    }
}
