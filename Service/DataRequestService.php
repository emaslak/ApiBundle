<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ApiBundle\Service;

use ONGR\ElasticsearchBundle\ORM\Repository;
use Symfony\Component\DependencyInjection\Container;
use ONGR\ElasticsearchBundle\ORM\Manager;
use ONGR\ElasticsearchBundle\DSL\Query\MatchAllQuery;
use ONGR\ElasticsearchBundle\DSL\Search;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class DataRequestService, for managing request to a Document repository.
 */
class DataRequestService
{
    /**
     * @var Manager Data Documents manager.
     */
    protected $dataManager;

    /**
     * @var Repository Data document's repository.
     */
    protected $dataRepository;

    /**
     * @param Container $container
     * @param string    $manager
     * @param string    $document
     * @param array     $fields
     */
    public function __construct(
        Container $container,
        $manager,
        $document,
        $fields
    ) {
        $this->dataManager = $container->get($manager);

        $this->dataRepository = $this->dataManager->getRepository($document);
    }

    /**
     * Repository getter.
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function get($request)
    {
        /** @var Search $search */
        $search = $this->dataRepository->createSearch();

        $query = new MatchAllQuery();

        $search->addQuery($query);

        return $this->dataRepository->execute($search, 'array');
    }

    /**
     * Creates response for get requests.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function getResponse(Request $request)
    {
        $format = $this->getResponseFormat($request);
        $mime = $this->getResponseMime($format);

        $data = $this->get($request);
        $data = $this->encodeArray($data, $format);

        $response = new Response();
        $response->setContent($data);
        $response->headers->set('Content-Type', $mime);

        return $response;
    }

    /**
     * Encodes array to given format.
     *
     * @param array  $data
     * @param string $format
     *
     * @return mixed
     * @throws \DomainException
     */
    private function encodeArray($data, $format)
    {
        switch($format){
            case 'json':
                return json_encode($data);
            default:
                throw new \DomainException("Unknown format \"{$format}\"");
        }
    }

    /**
     * @param Request $request
     *
     * @return string
     */
    private function getResponseFormat(Request $request)
    {
        return 'json';
    }

    /**
     * @param string $format
     *
     * @return string
     *
     * @throws \DomainException
     */
    private function getResponseMime($format)
    {
        static $types = [
            'json' => 'application/json',
        ];

        if (!isset($types[$format])) {
            throw new \DomainException("Unknown format \"{$format}\"");
        }

        return $types[$format];
    }
}
