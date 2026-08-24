<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Entity\Traits\OrderedMediaTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
#[Vich\Uploadable]
#[ApiResource(
    operations: [new Get(), new GetCollection(order: ['priority' => 'ASC', 'id' => 'ASC'])],
    normalizationContext: ['groups' => ['content:read']],
    paginationEnabled: false,
)]
#[ApiFilter(BooleanFilter::class, properties: ['active'])]
#[ApiFilter(OrderFilter::class, properties: ['priority', 'title'])]
class AnimationPoster
{
    use OrderedMediaTrait;

    #[Assert\Callback]
    public function validateImageOnly(ExecutionContextInterface $context): void
    {
        if ($this->getFile() && !str_starts_with((string) $this->getFile()->getMimeType(), 'image/')) {
            $context->buildViolation('Необходимо загрузить изображение-постер.')->atPath('file')->addViolation();
        }
    }
}
