<?php

namespace App\Command;

use App\Entity\MapArea;
use App\Entity\MapPlace;
use App\Entity\MapPlan;
use App\Enum\MapPlaceCategory;
use App\Enum\PlaceType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[AsCommand(name: 'app:import-map-infrastructure', description: 'Импортирует объекты инфраструктуры и их иконки из утверждённого ZIP-архива')]
final class ImportMapInfrastructureCommand extends Command
{
    /** @var list<array{name: string, category: string, priority: int, icon: string, aliases?: list<string>}> */
    public const OBJECTS = [
        ['name' => 'Бассейн', 'category' => 'sport', 'priority' => 1, 'icon' => 'бассейн.svg'],
        ['name' => 'Тренажерный зал', 'category' => 'sport', 'priority' => 2, 'icon' => 'тренажерный_зал.svg'],
        ['name' => 'Спортивный зал', 'category' => 'sport', 'priority' => 3, 'icon' => 'спортивный_зал.svg', 'aliases' => ['Спортивный центр']],
        ['name' => 'Боулинг', 'category' => 'sport', 'priority' => 4, 'icon' => 'боулинг.svg'],
        ['name' => 'Спортивная площадка', 'category' => 'sport', 'priority' => 5, 'icon' => 'спортивная_площадка.svg'],
        ['name' => 'Теннисный корт', 'category' => 'sport', 'priority' => 6, 'icon' => 'тенисный_корт.svg'],
        ['name' => 'Бадминтон', 'category' => 'sport', 'priority' => 7, 'icon' => 'бадминтон.svg'],
        ['name' => 'Тренажерный городок', 'category' => 'sport', 'priority' => 8, 'icon' => 'тренажерный_городок.svg'],
        ['name' => 'Настольный теннис', 'category' => 'sport', 'priority' => 9, 'icon' => 'настольный теннис.svg'],
        ['name' => 'Мини-гольф', 'category' => 'sport', 'priority' => 10, 'icon' => 'мини_гольф.svg'],

        ['name' => 'Обеденный зал', 'category' => 'recreation', 'priority' => 1, 'icon' => 'обеденный_зал.svg'],
        ['name' => 'Кафе', 'category' => 'recreation', 'priority' => 2, 'icon' => 'кафе.svg'],
        ['name' => 'Бар', 'category' => 'recreation', 'priority' => 3, 'icon' => 'бар.svg'],
        ['name' => 'Кинотеатр', 'category' => 'recreation', 'priority' => 4, 'icon' => 'кинотеатр.svg'],
        ['name' => 'Игровая комната', 'category' => 'recreation', 'priority' => 5, 'icon' => 'игровая комната.svg'],
        ['name' => 'Сауна', 'category' => 'recreation', 'priority' => 6, 'icon' => 'сауна.svg'],
        ['name' => 'Фито-бар', 'category' => 'recreation', 'priority' => 7, 'icon' => 'фито_бар.svg'],
        ['name' => 'Солнечные ванны', 'category' => 'recreation', 'priority' => 8, 'icon' => 'солнечные_ванны.svg'],
        ['name' => 'Беседка для барбекю', 'category' => 'recreation', 'priority' => 9, 'icon' => 'беседка_барбекю.svg'],
        ['name' => 'Мангальная зона', 'category' => 'recreation', 'priority' => 10, 'icon' => 'мангальная_зона.svg'],
        ['name' => 'Пергола', 'category' => 'recreation', 'priority' => 11, 'icon' => 'пергола.svg'],

        ['name' => 'Парковка', 'category' => 'other', 'priority' => 1, 'icon' => 'парковка.svg'],
        ['name' => 'Остановка трансфера', 'category' => 'other', 'priority' => 2, 'icon' => 'остановка_трансфера.svg'],
        ['name' => 'КПП', 'category' => 'other', 'priority' => 3, 'icon' => 'кпп.svg'],
        ['name' => 'Охрана', 'category' => 'other', 'priority' => 4, 'icon' => 'охрана.svg'],
        ['name' => 'Ресепшен', 'category' => 'other', 'priority' => 5, 'icon' => 'ресепшн.svg'],
        ['name' => 'Администрация', 'category' => 'other', 'priority' => 6, 'icon' => 'администрация.svg'],
        ['name' => 'Банкомат', 'category' => 'other', 'priority' => 7, 'icon' => 'банкомат.svg'],
        ['name' => 'Медицина', 'category' => 'other', 'priority' => 8, 'icon' => 'медицина.svg'],
        ['name' => 'Медпункт', 'category' => 'other', 'priority' => 9, 'icon' => 'медпункт.svg'],
        ['name' => 'Косметология', 'category' => 'other', 'priority' => 10, 'icon' => 'косметология.svg'],
        ['name' => 'Прачечная Сам Стирай', 'category' => 'other', 'priority' => 11, 'icon' => 'прачечная.svg'],
        ['name' => 'Детская площадка', 'category' => 'other', 'priority' => 12, 'icon' => 'детская_площадка.svg'],
        ['name' => 'Детская комната', 'category' => 'other', 'priority' => 13, 'icon' => 'детская_комната.svg'],
        ['name' => 'Прокат водного инвентаря', 'category' => 'other', 'priority' => 14, 'icon' => 'прокат_водного_транспорта.svg'],
        ['name' => 'Место для курения', 'category' => 'other', 'priority' => 15, 'icon' => 'место_для_курения.svg'],

        ['name' => 'Главный корпус', 'category' => 'buildings', 'priority' => 1, 'icon' => 'главный_корпус.svg'],
        ['name' => 'Лечебно-административный корпус', 'category' => 'buildings', 'priority' => 2, 'icon' => 'лечебно-админ_корпус.svg', 'aliases' => ['Медицинский центр']],
        ['name' => 'Спортивно-оздоровительный корпус', 'category' => 'buildings', 'priority' => 3, 'icon' => 'спортивно-оздорв_корпус.svg'],
        ['name' => 'Спортбаза (прокат спортинвентаря)', 'category' => 'buildings', 'priority' => 4, 'icon' => 'спортбаза.svg'],
        ['name' => 'Физкультурно-оздоровительный корпус', 'category' => 'buildings', 'priority' => 5, 'icon' => 'физкульт_оздоров_корпус.svg'],
        ['name' => 'Галерея', 'category' => 'buildings', 'priority' => 6, 'icon' => 'галерея.svg'],
        ['name' => 'Часовня Петра и Павла', 'category' => 'buildings', 'priority' => 7, 'icon' => 'часовня.svg'],
        ['name' => 'Лодочная станция', 'category' => 'buildings', 'priority' => 8, 'icon' => 'лодочная_станция.svg'],
    ];

    public function __construct(private readonly EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('archive', InputArgument::REQUIRED, 'Путь к ZIP-архиву с SVG-иконками')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Проверить архив и показать изменения без записи в БД');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $archive = (string) $input->getArgument('archive');
        $dryRun = (bool) $input->getOption('dry-run');
        if (!is_file($archive)) throw new \InvalidArgumentException('Архив не найден: '.$archive);

        $plan = $this->em->getRepository(MapPlan::class)->findOneBy(['active' => true], ['id' => 'ASC']);
        if (!$plan) throw new \LogicException('Сначала необходимо настроить активную карту территории.');
        $area = $this->em->getRepository(MapArea::class)->findOneBy(['plan' => $plan, 'active' => true], ['id' => 'ASC']);

        $zip = new \ZipArchive();
        if ($zip->open($archive) !== true) throw new \RuntimeException('Не удалось открыть ZIP-архив: '.$archive);

        $entries = $this->indexArchive($zip);
        foreach (self::OBJECTS as $object) {
            if (!isset($entries[$object['icon']])) {
                $zip->close();
                throw new \RuntimeException('В архиве отсутствует иконка: '.$object['icon']);
            }
        }

        $created = 0;
        $updated = 0;
        $temporaryFiles = [];

        try {
            foreach (self::OBJECTS as $object) {
                $place = $this->findPlace($plan, $object);
                $isNew = !$place;
                $place ??= new MapPlace();

                if ($isNew) {
                    $place->plan = $plan;
                    $place->area = $area;
                    $this->em->persist($place);
                    ++$created;
                } else {
                    ++$updated;
                }

                $oldName = $place->name;
                $place->name = $object['name'];
                $place->type = PlaceType::INFRASTRUCTURE;
                $place->category = MapPlaceCategory::from($object['category']);
                $place->priority = $object['priority'];
                $place->active = true;

                $action = $isNew ? 'создать' : 'обновить';
                if (!$isNew && $oldName !== $place->name) $action .= sprintf(' (%s → %s)', $oldName, $place->name);
                $output->writeln(sprintf('%-10s [%s:%02d] %s', $action, $place->category->value, $place->priority, $place->name));

                if ($dryRun) continue;

                $contents = $zip->getFromIndex($entries[$object['icon']]);
                if ($contents === false) throw new \RuntimeException('Не удалось прочитать иконку: '.$object['icon']);
                $temporaryFile = tempnam(sys_get_temp_dir(), 'aksakovo-icon-');
                if ($temporaryFile === false || file_put_contents($temporaryFile, $contents) === false) {
                    throw new \RuntimeException('Не удалось подготовить иконку: '.$object['icon']);
                }
                $temporaryFiles[] = $temporaryFile;
                $place->setIconFile(new UploadedFile($temporaryFile, $object['icon'], 'image/svg+xml', null, true));
            }

            if (!$dryRun) $this->em->flush();
        } finally {
            $zip->close();
            foreach ($temporaryFiles as $temporaryFile) {
                if (is_file($temporaryFile)) @unlink($temporaryFile);
            }
        }

        $output->writeln(sprintf('<info>%s: создано %d, обновлено %d, всего %d.</info>', $dryRun ? 'Проверка завершена' : 'Импорт завершён', $created, $updated, count(self::OBJECTS)));
        return Command::SUCCESS;
    }

    /** @return array<string, int> */
    private function indexArchive(\ZipArchive $zip): array
    {
        $entries = [];
        for ($index = 0; $index < $zip->numFiles; ++$index) {
            $entry = $zip->getNameIndex($index);
            if ($entry === false || str_ends_with($entry, '/')) continue;
            $entries[basename(str_replace('\\', '/', $entry))] = $index;
        }
        return $entries;
    }

    /** @param array{name: string, aliases?: list<string>} $object */
    private function findPlace(MapPlan $plan, array $object): ?MapPlace
    {
        $repository = $this->em->getRepository(MapPlace::class);
        $place = $repository->findOneBy(['plan' => $plan, 'name' => $object['name']]);
        if ($place) return $place;
        foreach ($object['aliases'] ?? [] as $alias) {
            $place = $repository->findOneBy(['plan' => $plan, 'name' => $alias]);
            if ($place) return $place;
        }
        return null;
    }
}
