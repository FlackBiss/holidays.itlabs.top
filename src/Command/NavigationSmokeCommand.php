<?php

namespace App\Command;

use App\ApiResource\NavigationRequest;
use App\Entity\{MapEdge,MapNode,MapPlace,MapPlan};
use App\Enum\PlaceType;
use App\Service\NavigationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

#[AsCommand(name:'app:navigation:smoke',description:'Проверяет GPS-привязку и поиск пути в откатываемой транзакции')]
final class NavigationSmokeCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $em,private readonly NavigationService $navigation) { parent::__construct(); }
    protected function execute(InputInterface $input,OutputInterface $output): int
    {
        $connection=$this->em->getConnection(); $connection->beginTransaction();
        try {
            $plan=new MapPlan(); $plan->title='smoke'; $plan->width=1000; $plan->height=1000; $this->em->persist($plan);
            $a=$this->node($plan,0,0,56.0340,37.6020); $b=$this->node($plan,100,0,56.0340,37.6030); $c=$this->node($plan,100,100,56.0350,37.6030);
            $this->edge($plan,$a,$b); $this->edge($plan,$b,$c);
            $place=new MapPlace(); $place->plan=$plan; $place->node=$c; $place->type=PlaceType::INFRASTRUCTURE; $place->name='smoke-destination'; $place->routeDrawn=false; $this->em->persist($place); $this->em->flush();
            $request=new NavigationRequest(); $request->destinationPlaceId=$place->getId(); $request->latitude=56.0340; $request->longitude=37.6025;
            try {
                $this->navigation->buildRoute($request);
                throw new \RuntimeException('Маршрут построен при выключенном флаге «Маршрут расчерчен».');
            } catch (UnprocessableEntityHttpException) {
                $output->writeln('<info>Undrawn route correctly rejected.</info>');
            }
            $place->routeDrawn=true; $this->em->flush();
            $route=$this->navigation->buildRoute($request);
            if (count($route->points)<3 || $route->snappedPosition===null || $route->distanceMeters<=0) throw new \RuntimeException('Маршрут вернул неполные данные.');
            $output->writeln(sprintf('<info>GPS route OK: %d points, %.1f m, snap %.1f m.</info>',count($route->points),$route->distanceMeters,$route->snapDistanceMeters));
            return Command::SUCCESS;
        } finally { if ($connection->isTransactionActive()) $connection->rollBack(); }
    }
    private function node(MapPlan $plan,float $x,float $y,float $lat,float $lon): MapNode { $n=new MapNode(); $n->plan=$plan; $n->x=$x; $n->y=$y; $n->latitude=$lat; $n->longitude=$lon; $this->em->persist($n); return $n; }
    private function edge(MapPlan $plan,MapNode $from,MapNode $to): void { $e=new MapEdge(); $e->plan=$plan; $e->fromNode=$from; $e->toNode=$to; $this->em->persist($e); }
}
