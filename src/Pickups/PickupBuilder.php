<?php

namespace SmartDato\GlsItaly\Pickups;

use DateTimeInterface;
use SmartDato\GlsItaly\Data\Credentials;
use SmartDato\GlsItaly\Data\PickupData;
use SmartDato\GlsItaly\Data\RecipientData;
use SmartDato\GlsItaly\Exceptions\ValidationException;
use SmartDato\GlsItaly\Pickups\Legacy\LegacyPickupConnector;
use SmartDato\GlsItaly\Pickups\Legacy\Requests\AddPickupRequest;
use SmartDato\GlsItaly\Pickups\Results\AddPickupResult;

class PickupBuilder
{
    protected ?string $contractCode = null;

    protected ?string $requesterName = null;

    protected string $bda = '';

    protected ?RecipientData $pickupAddress = null;

    protected ?DateTimeInterface $pickupDate = null;

    protected int $parcelCount = 1;

    protected float $weight = 0.0;

    protected ?RecipientData $deliveryAddress = null;

    protected string $note = '';

    protected string $notifyEmail = '';

    protected string $phone = '';

    protected ?string $pickupLocationEmail = null;

    /** @var array<int, string> */
    protected array $services = [];

    protected string $morningFrom = '08';

    protected string $morningTo = '13';

    protected string $afternoonFrom = '13';

    protected string $afternoonTo = '18';

    protected string $parcelType = '0';

    public function __construct(
        protected readonly LegacyPickupConnector $connector,
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

    public function requesterName(string $requesterName): self
    {
        $this->requesterName = $requesterName;

        return $this;
    }

    public function bda(string $bda): self
    {
        $this->bda = $bda;

        return $this;
    }

    public function pickupAddress(RecipientData $pickupAddress): self
    {
        $this->pickupAddress = $pickupAddress;

        return $this;
    }

    public function pickupDate(DateTimeInterface $pickupDate): self
    {
        $this->pickupDate = $pickupDate;

        return $this;
    }

    public function parcelCount(int $parcelCount): self
    {
        $this->parcelCount = $parcelCount;

        return $this;
    }

    public function weight(float $weight): self
    {
        $this->weight = $weight;

        return $this;
    }

    public function deliveryAddress(?RecipientData $deliveryAddress): self
    {
        $this->deliveryAddress = $deliveryAddress;

        return $this;
    }

    public function note(string $note): self
    {
        $this->note = $note;

        return $this;
    }

    public function notifyEmail(string $notifyEmail): self
    {
        $this->notifyEmail = $notifyEmail;

        return $this;
    }

    public function phone(string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    public function pickupLocationEmail(string $pickupLocationEmail): self
    {
        $this->pickupLocationEmail = $pickupLocationEmail;

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

    public function window(string $morningFrom, string $morningTo, string $afternoonFrom, string $afternoonTo): self
    {
        $this->morningFrom = $morningFrom;
        $this->morningTo = $morningTo;
        $this->afternoonFrom = $afternoonFrom;
        $this->afternoonTo = $afternoonTo;

        return $this;
    }

    public function parcelType(string $parcelType): self
    {
        $this->parcelType = $parcelType;

        return $this;
    }

    /**
     * @throws ValidationException
     */
    public function toData(): PickupData
    {
        $this->validate();

        return new PickupData(
            contractCode: $this->contractCode ?? (string) $this->credentials?->contractCode,
            requesterName: $this->requesterName,
            bda: $this->bda,
            pickupAddress: $this->pickupAddress,
            pickupDate: $this->pickupDate,
            parcelCount: $this->parcelCount,
            weight: $this->weight,
            deliveryAddress: $this->deliveryAddress,
            note: $this->note,
            notifyEmail: $this->notifyEmail,
            phone: $this->phone,
            pickupLocationEmail: $this->pickupLocationEmail,
            services: $this->services,
            morningFrom: $this->morningFrom,
            morningTo: $this->morningTo,
            afternoonFrom: $this->afternoonFrom,
            afternoonTo: $this->afternoonTo,
            parcelType: $this->parcelType,
        );
    }

    /**
     * @throws ValidationException
     */
    public function create(): AddPickupResult
    {
        if ($this->credentials === null) {
            throw new ValidationException('Credentials are required, call GlsItaly::withCredentials() or credentials() before create().');
        }

        $response = $this->connector->call(new AddPickupRequest($this->credentials, [$this->toData()]));

        return AddPickupResult::fromResponse($response);
    }

    protected function validate(): void
    {
        if ($this->contractCode === null && $this->credentials?->contractCode === null) {
            throw new ValidationException('A contract code is required, call contractCode() or pass it with the credentials before create().');
        }

        if ($this->requesterName === null) {
            throw new ValidationException('A requester name is required, call requesterName() before create().');
        }

        if ($this->pickupAddress === null) {
            throw new ValidationException('A pickup address is required, call pickupAddress() before create().');
        }

        if ($this->pickupDate === null) {
            throw new ValidationException('A pickup date is required, call pickupDate() before create().');
        }
    }
}
