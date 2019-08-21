<?php

/*
 * This file is part of PHP DNS Server.
 *
 * (c) Yif Swery <yiftachswr@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace yswery\DNS;

class Message
{
    /**
     * @var Header
     */
    private $header;

    /**
     * @var ResourceRecord[]
     */
    private $questions = [];

    /**
     * @var ResourceRecord[]
     */
    private $answers = [];

    /**
     * @var ResourceRecord[]
     */
    private $authoritatives = [];

    /**
     * @var ResourceRecord[]
     */
    private $additionals = [];

    /**
     * Message constructor.
     *
     * @param Header|null $header
     */
    public function __construct(Header $header = null)
    {
        if (null === $header) {
            $header = (new Header())
                ->setQuestionCount(0)
                ->setAnswerCount(0)
                ->setNameServerCount(0)
                ->setAdditionalRecordsCount(0);
        }
        $this->setHeader($header);
    }

    /**
     * @return Header
     */
    public function getHeader(): Header
    {
        return $this->header;
    }

    /**
     * @param Header $header
     *
     * @return Message
     */
    public function setHeader(Header $header): Message
    {
        $this->header = $header;

        return $this;
    }

    /**
     * @return ResourceRecord[]
     */
    public function getQuestions(): array
    {
        return $this->questions;
    }

    /**
     * @param ResourceRecord $resourceRecord
     *
     * @throws \InvalidArgumentException
     *
     * @return Message
     */
    public function addQuestion(ResourceRecord $resourceRecord): Message
    {
        if (!$resourceRecord->isQuestion()) {
            throw new \InvalidArgumentException('Resource Record provided is not a question.');
        }

        $this->questions[] = $resourceRecord;
        $this->header->setQuestionCount(count($this->questions));

        return $this;
    }

    /**
     * @return ResourceRecord[]
     */
    public function getAnswers(): array
    {
        return $this->answers;
    }

    /**
     * @param ResourceRecord $resourceRecord
     *
     * @return Message
     */
    public function addAnswer(ResourceRecord $resourceRecord): Message
    {
        $this->answers[] = $resourceRecord;
        $this->header->setAnswerCount(count($this->answers));

        return $this;
    }

    /**
     * @return ResourceRecord[]
     */
    public function getAuthoritatives(): array
    {
        return $this->authoritatives;
    }

    /**
     * @param ResourceRecord $resourceRecord
     *
     * @return Message
     */
    public function addAuthoritative(ResourceRecord $resourceRecord): Message
    {
        $this->authoritatives[] = $resourceRecord;
        $this->header->setNameServerCount(count($this->authoritatives));

        return $this;
    }

    /**
     * @return ResourceRecord[]
     */
    public function getAdditionals(): array
    {
        return $this->additionals;
    }

    /**
     * @param ResourceRecord $resourceRecord
     *
     * @return Message
     */
    public function addAdditional(ResourceRecord $resourceRecord): Message
    {
        $this->additionals[] = $resourceRecord;
        $this->header->setAdditionalRecordsCount(count($this->additionals));

        return $this;
    }

    /**
     * @param array $resourceRecords
     *
     * @return Message
     */
    public function setQuestions(array $resourceRecords): Message
    {
        $this->questions = [];
        foreach ($resourceRecords as $resourceRecord) {
            $this->addQuestion($resourceRecord);
        }

        return $this;
    }

    /**
     * @param array $resourceRecords
     *
     * @return Message
     */
    public function setAnswers(array $resourceRecords): Message
    {
        $this->answers = $resourceRecords;
        $this->header->setAnswerCount(count($this->answers));

        return $this;
    }

    /**
     * @param array $resourceRecords
     *
     * @return Message
     */
    public function setAuthoritatives(array $resourceRecords): Message
    {
        $this->authoritatives = $resourceRecords;
        $this->header->setNameServerCount(count($this->authoritatives));

        return $this;
    }

    /**
     * @param array $resourceRecords
     *
     * @return Message
     */
    public function setAdditionals(array $resourceRecords): Message
    {
        $this->additionals = $resourceRecords;
        $this->header->setAdditionalRecordsCount(count($this->additionals));

        return $this;
    }
}
