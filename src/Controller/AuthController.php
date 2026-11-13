<?php

namespace App\Controller;

use App\Dto\LoginRequest;
use App\Entity\ApiToken;
use App\Entity\User;
use App\Repository\ApiTokenRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api')]
final class AuthController extends AbstractController
{
    #[Route('/auth/login', methods: ['POST'])]
    public function login(
        #[MapRequestPayload] LoginRequest $dto,
        UserRepository $users,
        UserPasswordHasherInterface $hasher,
        EntityManagerInterface $em,
    ): JsonResponse {
        $user = $users->findOneBy(['email' => mb_strtolower($dto->email)]);

        if (!$user || !$hasher->isPasswordValid($user, $dto->password)) {
            return $this->json(['error' => 'Credenciais inválidas.'], Response::HTTP_UNAUTHORIZED);
        }

        $plain = bin2hex(random_bytes(32));
        $token = new ApiToken($user, hash('sha256', $plain), new \DateTimeImmutable('+8 hours'));
        $em->persist($token);
        $em->flush();

        return $this->json([
            'token' => $plain,
            'token_type' => 'Bearer',
            'expires_at' => $token->getExpiresAt()->format(DATE_ATOM),
        ]);
    }

    #[Route('/auth/logout', methods: ['POST'])]
    public function logout(Request $request, ApiTokenRepository $tokens, EntityManagerInterface $em): Response
    {
        $header = (string) $request->headers->get('Authorization');
        if (str_starts_with($header, 'Bearer ')) {
            $token = $tokens->findOneBy(['token' => hash('sha256', substr($header, 7))]);
            if ($token) {
                $em->remove($token);
                $em->flush();
            }
        }

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/me', methods: ['GET'])]
    public function me(#[CurrentUser] User $user): JsonResponse
    {
        return $this->json($user->toArray());
    }
}