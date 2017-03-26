<?php

namespace AppBundle\Controller;

use ContinuousPipe\Adapter\EnvironmentClientFactory;
use ContinuousPipe\Adapter\EnvironmentNotFound;
use ContinuousPipe\Model\Application;
use ContinuousPipe\Pipe\Uuid\UuidTransformer;
use ContinuousPipe\Security\Credentials\BucketRepository;
use ContinuousPipe\Security\Credentials\Cluster;
use ContinuousPipe\Security\Team\Team;
use Kubernetes\Client\Exception\ServerError;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use FOS\RestBundle\Controller\Annotations\View;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

/**
 * @Route(service="pipe.controllers.environment")
 */
class EnvironmentController extends Controller
{
    const RETRY_AFTER_SECONDS = 2;

    /**
     * @var EnvironmentClientFactory
     */
    private $environmentClientFactory;

    /**
     * @var BucketRepository
     */
    private $bucketRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param BucketRepository         $bucketRepository
     * @param EnvironmentClientFactory $environmentClientFactory
     */
    public function __construct(
        BucketRepository $bucketRepository,
        EnvironmentClientFactory $environmentClientFactory,
        LoggerInterface $logger
    ) {
        $this->bucketRepository = $bucketRepository;
        $this->environmentClientFactory = $environmentClientFactory;
        $this->logger = $logger;
    }

    /**
     * @Route("/teams/{teamSlug}/clusters/{clusterIdentifier}/environments", methods={"GET"})
     * @ParamConverter("team", converter="team", options={"slug"="teamSlug"})
     * @View
     */
    public function listAction(Request $request, Team $team, $clusterIdentifier)
    {
        try {
            $cluster = $this->getCluster($team, $clusterIdentifier);
            $environmentClient = $this->environmentClientFactory->getByCluster($cluster);

            if (is_array($labels = $request->query->get('labels'))) {
                return $environmentClient->findByLabels($labels);
            }

            return $environmentClient->findAll();
        } catch (ServerError $e) {
            $message = 'Collecting environment list from clusters failed.';
            $this->logger->warning($message, ['exception' => $e]);

            throw new ServiceUnavailableHttpException(self::RETRY_AFTER_SECONDS, $message);
        }
    }

    /**
     * @Route("/teams/{teamSlug}/clusters/{clusterIdentifier}/environments/{environmentIdentifier}", methods={"DELETE"})
     * @ParamConverter("team", converter="team", options={"slug"="teamSlug"})
     * @View
     */
    public function deleteAction(Team $team, $clusterIdentifier, $environmentIdentifier)
    {
        $client = $this->environmentClientFactory->getByCluster($this->getCluster($team, $clusterIdentifier));

        try {
            $environment = $client->find($environmentIdentifier);
        } catch (EnvironmentNotFound $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
            ], 404);
        }

        $client->delete($environment);
    }

    /**
     * @param Team   $team
     * @param string $clusterIdentifier
     *
     * @return Cluster
     */
    private function getCluster(Team $team, $clusterIdentifier)
    {
        $bucket = $this->bucketRepository->find(UuidTransformer::transform($team->getBucketUuid()));
        $matchingClusters = $bucket->getClusters()->filter(function (Cluster $cluster) use ($clusterIdentifier) {
            return $cluster->getIdentifier() == $clusterIdentifier;
        });

        if ($matchingClusters->count() == 0) {
            throw new NotFoundHttpException('Cluster is not found');
        }

        return $matchingClusters->first();
    }
}
