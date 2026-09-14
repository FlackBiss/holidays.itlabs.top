<?php
namespace App\Controller\Admin;
use App\Entity\GalleryMedia;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\{Action, Actions, Crud};
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGeneratorInterface;
use Symfony\Component\HttpFoundation\{Request, Response};

final class GalleryMediaCrudController extends AbstractOrderedMediaCrudController
{
    public static function getEntityFqcn(): string { return GalleryMedia::class; }
    protected static function label(): string { return 'Фотогалерея'; }
    protected static function verticalLayout(): bool { return true; }

    public function configureFields(string $pageName): iterable
    {
        foreach (parent::configureFields($pageName) as $field) {
            yield $field->getAsDto()->getProperty() === 'url'
                ? TextField::new('url', 'Превью')->onlyOnIndex()->setTemplatePath('admin/field/gallery_preview.html.twig')
                : $field;
        }
    }

    public function configureActions(Actions $actions): Actions
    {
        return parent::configureActions($actions)->add(Crud::PAGE_INDEX,
            Action::new('reorder', 'Порядок отображения', 'fas fa-arrows-up-down-left-right')
                ->linkToCrudAction('reorder')->createAsGlobalAction());
    }

    #[AdminRoute('/reorder', name: 'reorder', options: ['methods' => ['GET', 'POST']])]
    public function reorder(Request $request, EntityManagerInterface $em, AdminUrlGeneratorInterface $urls): Response
    {
        $items = $em->getRepository(GalleryMedia::class)->findBy([], ['priority' => 'ASC', 'id' => 'ASC']);
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('gallery_order', $request->request->getString('_token'))) {
                throw $this->createAccessDeniedException('Недействительный токен формы. Обновите страницу.');
            }
            $ids = $request->request->all('order');
            if (array_filter($ids, static fn ($id): bool => !is_string($id)) !== []) {
                throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException('Некорректный порядок.');
            }
            $expected = array_map(static fn (GalleryMedia $item): string => (string) $item->getId(), $items);
            $sorted = $ids;
            sort($sorted, SORT_STRING);
            sort($expected, SORT_STRING);
            if ($sorted !== $expected) {
                $this->addFlash('warning', 'Состав галереи изменился или порядок некорректен. Повторите сортировку.');
                return $this->redirect($urls->unsetAll()->setController(self::class)->setAction('reorder')->generateUrl());
            }
            $positions = array_flip($ids);
            foreach ($items as $item) {
                $item->priority = $positions[(string) $item->getId()] + 1;
            }
            $em->flush();
            $this->addFlash('success', 'Порядок отображения сохранён.');
            return $this->redirect($urls->unsetAll()->setController(self::class)->setAction(Crud::PAGE_INDEX)->generateUrl());
        }
        return $this->render('admin/gallery_order.html.twig', [
            'items' => $items,
            'back_url' => $urls->unsetAll()->setController(self::class)->setAction(Crud::PAGE_INDEX)->generateUrl(),
        ]);
    }
}
