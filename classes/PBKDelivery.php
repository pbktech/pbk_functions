<?php

class PBKDelivery {

    private int $orderID;
    private array $contact;
    private string $service;
    private float $subTotal;
    private float $tax;
    private float $tip;
    private array $orderItems;
    private array $address;
    private string $orderDate;
    private string $expected;
    private string $config;
    private string $deliveryID;
    private object $request;
    private array $postFields;
    private array $headers;
    private object $guest;
    protected const textIdentifier = "7a6cf320-4afa-4f84-97a9-a37b3a287aca";

    public function __construct(string $service = null) {
        if (!empty($service)) {
            $this->setService($service);
        }
    }

    public function sendRequest(string $endpoint): ?object {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($this->request));
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        $result = curl_exec($ch);
        return json_decode($result);
    }

    /**
     * @return int
     */
    public function getOrderID(): int {
        return $this->orderID;
    }

    /**
     * @param int $orderID
     */
    public function setOrderID(int $orderID): void {
        $this->orderID = $orderID;
    }

    /**
     * @return array
     */
    public function getContact(): array {
        return $this->contact;
    }

    /**
     * @param array $contact
     */
    public function setContact(array $contact): void {
        $this->contact = $contact;
    }

    /**
     * @return float
     */
    public function getSubTotal(): float {
        return $this->subTotal;
    }

    /**
     * @param float $subTotal
     */
    public function setSubTotal(float $subTotal): void {
        $this->subTotal = $subTotal;
    }

    /**
     * @return float
     */
    public function getTax(): float {
        return $this->tax;
    }

    /**
     * @param float $tax
     */
    public function setTax(float $tax): void {
        $this->tax = $tax;
    }

    /**
     * @return float
     */
    public function getTip(): float {
        return $this->tip;
    }

    /**
     * @param float $tip
     */
    public function setTip(float $tip): void {
        $this->tip = $tip;
    }

    /**
     * @return array
     */
    public function getOrderItems(): array {
        return $this->orderItems;
    }

    /**
     * @param array $orderItems
     */
    public function setOrderItems(array $orderItems): void {
        $this->orderItems = $orderItems;
    }

    /**
     * @return array
     */
    public function getAddress(): array {
        return $this->address;
    }

    /**
     * @param array $address
     */
    public function setAddress(array $address): void {
        $this->address = $address;
    }

    /**
     * @return string
     */
    public function getOrderDate(): string {
        return $this->orderDate;
    }

    /**
     * @param string $orderDate
     */
    public function setOrderDate(string $orderDate): void {
        $this->orderDate = $orderDate;
    }

    /**
     * @return string
     */
    public function getExpected(): string {
        return $this->expected;
    }

    /**
     * @param string $expected
     */
    public function setExpected(string $expected): void {
        $this->expected = $expected;
    }

    /**
     * @return string
     */
    public function getService(): string {
        return $this->service;
    }

    /**
     * @param string $service
     */
    public function setService(string $service): void {
        $this->service = $service;
    }

    /**
     * @return string
     */
    public function getConfig(): string {
        return $this->config;
    }

    /**
     * @param string $config
     */
    public function setConfig(string $config): void {
        $this->config = $config;
    }

    /**
     * @return string
     */
    public function getDeliveryID(): string {
        return $this->deliveryID;
    }

    /**
     * @param string $deliveryID
     */
    public function setDeliveryID(string $deliveryID): void {
        $this->deliveryID = $deliveryID;
    }

    /**
     * @return object
     */
    public function getRequest(): object {
        return $this->request;
    }

    /**
     * @param object $request
     */
    public function setRequest(object $request): void {
        $this->request = $request;
    }

    /**
     * @return array
     */
    public function getPostFields(): array {
        return $this->postFields;
    }

    /**
     * @param array $postFields
     */
    public function setPostFields(array $postFields): void {
        $this->postFields = $postFields;
    }

    /**
     * @return array
     */
    public function getHeaders(): array {
        return $this->headers;
    }

    /**
     * @param array $headers
     */
    public function setHeaders(array $headers): void {
        $this->headers = $headers;
    }

    /**
     * @return array
     */
    public function getGuest(): object {
        return $this->guest;
    }

    /**
     * @param array $guest
     */
    public function setGuest(object $guest): void {
        $this->guest = $guest;
    }

}