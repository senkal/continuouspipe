<?php

namespace ApiBundle\Controller;

use ContinuousPipe\Billing\BillingProfile\Request\UserBillingProfileCreationRequest;
use ContinuousPipe\Billing\BillingProfile\UserBillingProfile;
use ContinuousPipe\Billing\BillingProfile\UserBillingProfileCreator;
use ContinuousPipe\Billing\BillingProfile\UserBillingProfileNotFound;
use ContinuousPipe\Billing\BillingProfile\UserBillingProfileRepository;
use ContinuousPipe\Billing\Subscription\SubscriptionClient;
use ContinuousPipe\Security\User\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use FOS\RestBundle\Controller\Annotations\View;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @Route(service="api.controller.billing_profile")
 */
class BillingProfileController
{
    /**
     * @var UserBillingProfileRepository
     */
    private $userBillingProfileRepository;

    /**
     * @var UserBillingProfileCreator
     */
    private $userBillingProfileCreator;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var SubscriptionClient
     */
    private $subscriptionClient;

    public function __construct(
        UserBillingProfileRepository $userBillingProfileRepository,
        UserBillingProfileCreator $userBillingProfileCreator,
        ValidatorInterface $validator,
        SubscriptionClient $subscriptionClient
    ) {
        $this->userBillingProfileRepository = $userBillingProfileRepository;
        $this->userBillingProfileCreator = $userBillingProfileCreator;
        $this->validator = $validator;
        $this->subscriptionClient = $subscriptionClient;
    }

    /**
     * @Route("/me/billing-profile", methods={"GET"})
     * @ParamConverter("user", converter="user", options={"fromSecurityContext"=true})
     * @View
     */
    public function getAction(User $user)
    {
        return $this->billingProfiles($user);
    }

    /**
     * @Route("/me/billing-profiles", methods={"GET"})
     * @ParamConverter("user", converter="user", options={"fromSecurityContext"=true})
     * @View
     */
    public function billingProfiles(User $user)
    {
        $billingProfiles = $this->userBillingProfileRepository->findByUser($user);

        if (count($billingProfiles) == 0) {
            throw new UserBillingProfileNotFound(
                sprintf('No billing profiles found for user "%s"', $user->getUsername())
            );
        }

        return $billingProfiles;
    }

    /**
     * @Route("/me/billing-profiles", methods={"POST"})
     * @ParamConverter("user", converter="user", options={"fromSecurityContext"=true})
     * @ParamConverter("creationRequest", converter="fos_rest.request_body")
     * @View(statusCode=201)
     */
    public function createAction(UserBillingProfileCreationRequest $creationRequest, User $user)
    {
        $errors = $this->validator->validate($creationRequest);

        if ($errors->count() > 0) {
            return new JsonResponse(
                [
                    'message' => $errors->get(0)->getMessage(),
                ],
                400
            );
        }

        return $this->userBillingProfileCreator->create($creationRequest, $user);
    }

    /**
     * @Route("/billing-profile/{uuid}", methods={"GET"})
     * @ParamConverter("billingProfile", converter="billingProfile")
     * @Security("is_granted('READ', billingProfile)")
     * @View
     */
    public function getBillingProfileAction(UserBillingProfile $billingProfile)
    {
        return $billingProfile;
    }

    /**
     * @Route("/billing-profile/{uuid}/admins/{username}", methods={"POST"})
     * @ParamConverter("billingProfile", converter="billingProfile")
     * @ParamConverter("user", converter="user", options={"byUsername": "username"})
     * @Security("is_granted('READ', billingProfile)")
     * @View
     */
    public function addBillingProfileAdminAction(UserBillingProfile $billingProfile, User $user)
    {
        $billingProfile->getAdmins()->add($user);

        $this->userBillingProfileRepository->save($billingProfile);

        return $billingProfile;
    }

    /**
     * @Route("/billing-profile/{uuid}/admins/{username}", methods={"DELETE"})
     * @ParamConverter("billingProfile", converter="billingProfile")
     * @ParamConverter("user", converter="user", options={"byUsername": "username"})
     * @Security("is_granted('READ', billingProfile)")
     * @View
     */
    public function removeBillingProfileAdminAction(UserBillingProfile $billingProfile, User $user)
    {
        $matchingUsers = $billingProfile->getAdmins()->filter(function (User $admin) use ($user) {
            return $admin->getUsername() == $user->getUsername();
        });

        foreach ($matchingUsers as $matchingUser) {
            $billingProfile->getAdmins()->removeElement($matchingUser);
        }

        $this->userBillingProfileRepository->save($billingProfile);

        return $billingProfile;
    }

    /**
     * @Route("/billing-profile/{uuid}/subscriptions", methods={"GET"})
     * @ParamConverter("billingProfile", converter="billingProfile")
     * @Security("is_granted('READ', billingProfile)")
     * @View
     */
    public function getSubscriptionsAction(UserBillingProfile $billingProfile)
    {
        return $this->subscriptionClient->findSubscriptionsForBillingProfile($billingProfile);
    }
}
