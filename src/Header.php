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

class Header
{
    const OPCODE_STANDARD_QUERY = 0;

    const OPCODE_INVERSE_QUERY = 1;

    const OPCODE_STATUS_REQUEST = 2;

    const RCODE_NO_ERROR = 0;

    const RCODE_FORMAT_ERROR = 1;

    const RCODE_SERVER_FAILURE = 2;

    const RCODE_NAME_ERROR = 3;

    const RCODE_NOT_IMPLEMENTED = 4;

    const RCODE_REFUSED = 5;

    /**
     * ID.
     *
     * @var int
     */
    private $id;

    /**
     * QR.
     *
     * @var bool
     */
    private $response;

    /**
     * OPCODE.
     *
     * @var int
     */
    private $opcode;

    /**
     * AA.
     *
     * @var bool
     */
    private $authoritative;

    /**
     * TC.
     *
     * @var bool
     */
    private $truncated;

    /**
     * RD.
     *
     * @var bool
     */
    private $recursionDesired;

    /**
     * RA.
     *
     * @var bool
     */
    private $recursionAvailable;

    /**
     * A.
     *
     * @var int
     */
    private $z = 0;

    /**
     * RCODE.
     *
     * @var int
     */
    private $rcode;

    /**
     * QDCOUNT.
     *
     * @var int
     */
    private $questionCount;

    /**
     * ANCOUNT.
     *
     * @var int
     */
    private $answerCount;

    /**
     * NSCOUNT.
     *
     * @var int
     */
    private $nameServerCount;

    /**
     * ARCOUNT.
     *
     * @var int
     */
    private $additionalRecordsCount;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = (int) $id;

        return $this;
    }

    /**
     * @return bool
     */
    public function isQuery()
    {
        return !$this->response;
    }

    /**
     * @return bool
     */
    public function isResponse()
    {
        return $this->response;
    }

    /**
     * @param $response
     *
     * @return $this
     */
    public function setResponse($response)
    {
        $this->response = (bool) $response;

        return $this;
    }

    /**
     * @param $query
     *
     * @return $this
     */
    public function setQuery($query)
    {
        $this->response = !((bool) $query);

        return $this;
    }

    /**
     * @return int
     */
    public function getOpcode()
    {
        return $this->opcode;
    }

    /**
     * @param $opcode
     *
     * @return $this
     */
    public function setOpcode($opcode)
    {
        $this->opcode = (int) $opcode;

        return $this;
    }

    /**
     * @return bool
     */
    public function isAuthoritative()
    {
        return $this->authoritative;
    }

    /**
     * @param $authoritative
     *
     * @return $this
     */
    public function setAuthoritative($authoritative)
    {
        $this->authoritative = (bool) $authoritative;

        return $this;
    }

    /**
     * @return bool
     */
    public function isTruncated()
    {
        return $this->truncated;
    }

    /**
     * @param $truncated
     *
     * @return $this
     */
    public function setTruncated($truncated)
    {
        $this->truncated = (bool) $truncated;

        return $this;
    }

    /**
     * @return bool
     */
    public function isRecursionDesired()
    {
        return $this->recursionDesired;
    }

    /**
     * @param $recursionDesired
     *
     * @return $this
     */
    public function setRecursionDesired($recursionDesired)
    {
        $this->recursionDesired = (bool) $recursionDesired;

        return $this;
    }

    /**
     * @return bool
     */
    public function isRecursionAvailable()
    {
        return $this->recursionAvailable;
    }

    /**
     * @param $recursionAvailable
     *
     * @return $this
     */
    public function setRecursionAvailable($recursionAvailable)
    {
        $this->recursionAvailable = (bool) $recursionAvailable;

        return $this;
    }

    /**
     * @return int
     */
    public function getZ()
    {
        return $this->z;
    }

    /**
     * @param $z
     *
     * @return $this
     */
    public function setZ($z)
    {
        $this->z = (int) $z;

        return $this;
    }

    /**
     * @return int
     */
    public function getRcode()
    {
        return $this->rcode;
    }

    /**
     * @param $rcode
     *
     * @return $this
     */
    public function setRcode($rcode)
    {
        $this->rcode = (int) $rcode;

        return $this;
    }

    /**
     * @return int
     */
    public function getQuestionCount()
    {
        return $this->questionCount;
    }

    /**
     * @param $questionCount
     *
     * @return $this
     */
    public function setQuestionCount($questionCount)
    {
        $this->questionCount = (int) $questionCount;

        return $this;
    }

    /**
     * @return int
     */
    public function getAnswerCount()
    {
        return $this->answerCount;
    }

    /**
     * @param $answerCount
     *
     * @return $this
     */
    public function setAnswerCount($answerCount)
    {
        $this->answerCount = (int) $answerCount;

        return $this;
    }

    /**
     * @return int
     */
    public function getNameServerCount()
    {
        return $this->nameServerCount;
    }

    /**
     * @param $nameServerCount
     *
     * @return $this
     */
    public function setNameServerCount($nameServerCount)
    {
        $this->nameServerCount = (int) $nameServerCount;

        return $this;
    }

    /**
     * @return int
     */
    public function getAdditionalRecordsCount()
    {
        return $this->additionalRecordsCount;
    }

    /**
     * @param $additionalRecordsCount
     *
     * @return $this
     */
    public function setAdditionalRecordsCount($additionalRecordsCount)
    {
        $this->additionalRecordsCount = (int) $additionalRecordsCount;

        return $this;
    }
}
