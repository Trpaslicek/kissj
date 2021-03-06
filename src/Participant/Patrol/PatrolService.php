<?php

namespace kissj\Participant\Patrol;

use kissj\FlashMessages\FlashMessagesBySession;
use kissj\Mailer\PhpMailerWrapper;
use kissj\Participant\Admin\StatisticValueObject;
use kissj\Payment\PaymentRepository;
use kissj\Payment\PaymentService;
use kissj\User\User;
use kissj\User\UserService;

class PatrolService {
    private $patrolLeaderRepository;
    private $patrolParticipantRepository;
    private $paymentRepository;
    private $userService;
    private $paymentService;
    private $mailer;
    private $flashMessages;

    public function __construct(
        PatrolLeaderRepository $patrolLeaderRepository,
        PatrolParticipantRepository $patrolParticipantRepository,
        PaymentRepository $paymentRepository,
        UserService $userService,
        PaymentService $paymentService,
        FlashMessagesBySession $flashMessages,
        PhpMailerWrapper $mailer
    ) {
        $this->patrolLeaderRepository = $patrolLeaderRepository;
        $this->patrolParticipantRepository = $patrolParticipantRepository;
        $this->paymentRepository = $paymentRepository;
        $this->userService = $userService;
        $this->paymentService = $paymentService;
        $this->flashMessages = $flashMessages;
        $this->mailer = $mailer;
    }

    public function getPatrolLeader(User $user): PatrolLeader {
        if ($this->patrolLeaderRepository->countBy(['user' => $user]) === 0) {
            $patrolLeader = new PatrolLeader();
            $patrolLeader->user = $user;
            $this->patrolLeaderRepository->persist($patrolLeader);
        }

        return $this->patrolLeaderRepository->findOneBy(['user' => $user]);
    }

    public function addParamsIntoPatrolLeader(PatrolLeader $pl, array $params): PatrolLeader {
        $pl->firstName = $params['firstName'] ?? null;
        $pl->lastName = $params['lastName'] ?? null;
        $pl->nickname = $params['nickname'] ?? null;
        if ($params['birthDate'] !== null) {
            $pl->birthDate = new \DateTime($params['birthDate']);
        }
        $pl->gender = $params['gender'] ?? null;
        $pl->email = $params['email'] ?? null;
        $pl->telephoneNumber = $params['telephoneNumber'] ?? null;
        $pl->permanentResidence = $params['permanentResidence'] ?? null;
        $pl->country = $params['country'] ?? null;
        $pl->scoutUnit = $params['scoutUnit'] ?? null;
        /* $pl->setTshirt($params['tshirtShape'] ?? null, $params['tshirtSize'] ?? null); */
        $pl->foodPreferences = $params['foodPreferences'] ?? null;
        $pl->healthProblems = $params['healthProblems'] ?? null;
        $pl->languages = $params['languages'] ?? null;
        $pl->swimming = $params['swimming'] ?? null;
        $pl->patrolName = $params['patrolName'] ?? null;
        $pl->notes = $params['notes'] ?? null;

        return $pl;
    }

    public function isPatrolLeaderValidForClose(PatrolLeader $pl): bool {
        if (
            $pl->patrolName === null
            || $pl->firstName === null
            || $pl->lastName === null
            || $pl->birthDate === null
            || $pl->gender === null
            || $pl->email === null
            || $pl->telephoneNumber === null
            || $pl->permanentResidence === null
            || $pl->country === null
            || $pl->scoutUnit === null
            || $pl->foodPreferences === null
            || $pl->languages === null
            || $pl->swimming === null
            /*|| $pl->getTshirtShape() === null
            || $pl->getTshirtSize() === null*/
        ) {
            return false;
        }

        if (!empty($pl->email) && filter_var($pl->email, FILTER_VALIDATE_EMAIL) === false) {
            return false;
        }

        return true;
    }

    public function addPatrolParticipant(PatrolLeader $patrolLeader): PatrolParticipant {
        $patrolParticipant = new PatrolParticipant();
        $patrolParticipant->patrolLeader = $patrolLeader;

        $this->patrolParticipantRepository->persist($patrolParticipant);

        return $patrolParticipant;
    }

    public function getPatrolParticipant(int $patrolParticipantId): PatrolParticipant {
        $patrolParticipant = $this->patrolParticipantRepository->findOneBy(['id' => $patrolParticipantId]);

        return $patrolParticipant;
    }

    public function addParamsIntoPatrolParticipant(PatrolParticipant $p, array $params): PatrolParticipant {
        $p->firstName = $params['firstName'] ?? null;
        $p->lastName = $params['lastName'] ?? null;
        $p->nickname = $params['nickname'] ?? null;
        if ($params['birthDate'] !== null) {
            $p->birthDate = new \DateTime($params['birthDate']);
        }
        $p->gender = $params['gender'] ?? null;
        $p->email = $params['email'] ?? null;
        $p->telephoneNumber = $params['telephoneNumber'] ?? null;
        $p->permanentResidence = $params['permanentResidence'] ?? null;
        $p->country = $params['country'] ?? null;
        $p->scoutUnit = $params['scoutUnit'] ?? null;
        $p->foodPreferences = $params['foodPreferences'] ?? null;
        $p->healthProblems = $params['healthProblems'] ?? null;
        $p->swimming = $params['swimming'] ?? null;
        $p->notes = $params['notes'] ?? null;

        return $p;
    }

    public function isPatrolParticipantValidForClose(PatrolParticipant $p): bool {
        if (
            $p->firstName === null
            || $p->lastName === null
            || $p->birthDate === null
            || $p->gender === null
            || $p->email === null
            || $p->telephoneNumber === null
            || $p->permanentResidence === null
            || $p->country === null
            || $p->scoutUnit === null
            || $p->foodPreferences === null
            || $p->swimming === null
        ) {
            return false;
        }

        if (!empty($p->email) && filter_var($p->email, FILTER_VALIDATE_EMAIL) === false) {
            return false;
        }

        return true;
    }

    // TODO add telephone check 
    // check for numbers and plus sight up front only
    /*if ((!empty ($telephoneNumber)) && preg_match('/^\+?\d+$/', $telephoneNumber) === 0) {
        $validFlag = false;
    }*/

    /**
     * @param PatrolParticipant $patrolParticipant
     * @throws \LeanMapper\Exception\InvalidStateException
     */
    public function deletePatrolParticipant(PatrolParticipant $patrolParticipant) {
        $this->patrolParticipantRepository->delete($patrolParticipant);
    }

    public function patrolParticipantBelongsPatrolLeader(
        PatrolParticipant $patrolParticipant,
        PatrolLeader $patrolLeader
    ): bool {
        return $patrolParticipant->patrolLeader->id === $patrolLeader->id;
    }

    public function isCloseRegistrationValid(PatrolLeader $patrolLeader): bool {
        $event = $patrolLeader->user->event;
        if ($event->maximalClosedPatrolsCount <= $this->userService->getClosedPatrolsCount()) {
            $this->flashMessages->warning('Cannot lock the registration - for Patrols we have full registration now. Please wait for limit rise');

            return false;
        }

        switch ($patrolLeader->country) {
            case 'Slovak':
                $localMaxNumber = $event->maximalClosedPatrolsSlovakCount;
                break;
            case 'Czech':
                $localMaxNumber = $event->maximalClosedPatrolsCzechCount;
                break;
            case 'other':
                $localMaxNumber = $event->maximalClosedPatrolsOthersCount;
                break;
            default:
                $this->flashMessages->warning('Cannot determine your country properly');

                return false;
        }
        if ($localMaxNumber <= $this->userService->getClosedPatrolsCount()) {
            $this->flashMessages->warning('Cannot lock the registration - for Patrols from your country 
                we have full registration now. Please wait for limit rise');

            return false;
        }

        $validityFlag = true;
        if (!$this->isPatrolLeaderValidForClose($patrolLeader)) {
            $this->flashMessages->warning('Cannot lock the registration - some of your details are wrong or missing (probably email or some date)');

            $validityFlag = false;
        }

        $participants = $this->patrolParticipantRepository->findBy(['patrol_leader_id' => $patrolLeader->id]);
        $participantsCount = count($participants);
        if ($participantsCount < $event->minimalPatrolParticipantsCount) {
            $this->flashMessages->warning('Cannot lock the registration - too few participants, they are only '
                .$participantsCount.' from '.$event->minimalPatrolParticipantsCount.' needed');

            $validityFlag = false;
        }
        if ($participantsCount > $event->maximalPatrolParticipantsCount) {
            $this->flashMessages->warning('Cannot lock the registration - too many participants - they are '
                .$participantsCount.' and you need '.$event->maximalPatrolParticipantsCount.' maximum');

            $validityFlag = false;
        }
        /** @var PatrolParticipant $participant */
        foreach ($participants as $participant) {
            if (!$this->isPatrolParticipantValidForClose($participant)) {
                $this->flashMessages->warning('Cannot lock the registration - some of the '
                    .$participant->getFullName().' details are wrong or missing (probably email or some date)');

                $validityFlag = false;
            }
        }

        // to show all warnings
        return $validityFlag;
    }

    public function closeRegistration(PatrolLeader $patrolLeader): PatrolLeader {
        if ($this->isCloseRegistrationValid($patrolLeader)) {
            $this->userService->closeRegistration($patrolLeader->user);
            $this->mailer->sendRegistrationClosed($patrolLeader->user);
        }

        return $patrolLeader;
    }

    public function openRegistration(PatrolLeader $patrolLeader, string $reason): PatrolLeader {
        $this->mailer->sendDeniedRegistration($patrolLeader, $reason);
        $this->userService->openRegistration($patrolLeader->user);

        return $patrolLeader;
    }

    public function approveRegistration(PatrolLeader $patrolLeader): PatrolLeader {
        $price = $this->paymentService->getPrice($patrolLeader);
        $payment = $this->paymentRepository->createAndPersistNewPayment($patrolLeader, $price);

        $this->mailer->sendRegistrationApprovedWithPayment($patrolLeader, $payment);
        $this->userService->approveRegistration($patrolLeader->user);

        return $patrolLeader;
    }

    public function getAllPatrolsStatistics(): StatisticValueObject {
        $patrolLeaders = $this->patrolLeaderRepository->findAll();

        return new StatisticValueObject($patrolLeaders);
    }
}
