<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Renderer;

use App\Entity\Activity;
use App\Entity\ActivityMeta;
use App\Entity\Customer;
use App\Entity\CustomerMeta;
use App\Entity\MetaTableTypeInterface;
use App\Entity\Project;
use App\Entity\ProjectMeta;
use App\Entity\Tag;
use App\Entity\Timesheet;
use App\Entity\TimesheetMeta;
use App\Entity\User;
use App\Event\ActivityMetaDisplayEvent;
use App\Event\CustomerMetaDisplayEvent;
use App\Event\ProjectMetaDisplayEvent;
use App\Event\TimesheetMetaDisplayEvent;
use App\Export\ExportRendererInterface;
use App\Repository\Query\TimesheetQuery;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractRendererTestCase extends KernelTestCase
{
    protected function render(ExportRendererInterface $renderer): Response
    {
        $customer = new Customer('Customer Name');
        $customer->setNumber('A-0123456789');
        $customer->setVatId('DE-9876543210');
        $customer->setMetaField((new CustomerMeta())->setName('customer-foo')->setValue('customer-bar')->setIsVisible(true));

        $project = new Project();
        $project->setName('project name');
        $project->setCustomer($customer);
        $project->setOrderNumber('ORDER-123');
        $project->setMetaField((new ProjectMeta())->setName('project-bar')->setValue('project-bar')->setIsVisible(true));
        $project->setMetaField((new ProjectMeta())->setName('project-foo2')->setValue('project-foo2')->setIsVisible(true));

        $activity = new Activity();
        $activity->setName('activity description');
        $activity->setProject($project);
        $activity->setMetaField((new ActivityMeta())->setName('activity-foo')->setValue('activity-bar')->setIsVisible(true));

        $userMethods = ['getId', 'getPreferenceValue', 'getUsername', 'getUserIdentifier', 'getAlias'];
        $user1 = $this->getMockBuilder(User::class)->onlyMethods($userMethods)->disableOriginalConstructor()->getMock();
        $user1->method('getId')->willReturn(1);
        $user1->method('getPreferenceValue')->willReturn('50');
        $user1->method('getAlias')->willReturn('Foo Bar');
        $user1->method('getUserIdentifier')->willReturn('foo-bar');
        $user1->method('getUsername')->willReturn('foo-bar');

        $user2 = $this->getMockBuilder(User::class)->onlyMethods($userMethods)->disableOriginalConstructor()->getMock();
        $user2->method('getId')->willReturn(2);
        $user2->method('getAlias')->willReturn('Hello World');
        $user2->method('getUserIdentifier')->willReturn('hello-world');
        $user2->method('getUsername')->willReturn('hello-world');

        $timesheet = new Timesheet();
        $timesheet
            ->setDuration(3600) // 60 minutes
            ->setRate(293.27)
            ->setUser($user1)
            ->setActivity($activity)
            ->setProject($project)
            ->setBegin(new \DateTime())
            ->setEnd(new \DateTime())
        ;

        $timesheet2 = new Timesheet();
        $timesheet2
            ->setDuration(400)
            ->setRate(84.75)
            ->setUser($user2)
            ->setActivity($activity)
            ->setProject($project)
            ->setBegin(new \DateTime())
            ->setEnd(new \DateTime())
        ;

        $timesheet3 = new Timesheet();
        $timesheet3
            ->setDuration(1800)
            ->setRate(111.11)
            ->setUser($user1)
            ->setActivity($activity)
            ->setProject($project)
            ->setBegin(new \DateTime())
            ->setEnd(new \DateTime())
        ;

        $timesheet4 = new Timesheet();
        $timesheet4
            ->setDuration(400)
            ->setRate(1947.99)
            ->setUser($user2)
            ->setDescription('== jhg ljhg ') // make sure that spreadsheets don't render it as formula
            ->setActivity($activity)
            ->setProject($project)
            ->setBegin(new \DateTime())
            ->setEnd(new \DateTime())
            ->addTag((new Tag())->setName('foo'))
        ;

        $userKevin = new User();
        $userKevin->setAlias('Kevin');
        $userKevin->setUserIdentifier('kevin');

        $timesheet5 = new Timesheet();
        $timesheet5
            ->setDuration(400)
            ->setFixedRate(84)
            ->setUser($userKevin)
            ->setActivity($activity)
            ->setProject($project)
            ->setBegin(new \DateTime('2019-06-16 12:00:00'))
            ->setEnd(new \DateTime('2019-06-16 12:06:40'))
            ->addTag((new Tag())->setName('foo'))
            ->addTag((new Tag())->setName('bar'))
            ->setMetaField((new TimesheetMeta())->setName('foo')->setValue('meta-bar')->setIsVisible(true))
            ->setMetaField((new TimesheetMeta())->setName('foo2')->setValue('meta-bar2')->setIsVisible(true))
        ;

        $userNivek = new User();
        $userNivek->setAlias('niveK');
        $userNivek->setUserIdentifier('nivek');

        $timesheet6 = new Timesheet();
        $timesheet6
            ->setDuration(400)
            ->setFixedRate(-100.92)
            ->setUser($userNivek)
            ->setActivity($activity)
            ->setProject($project)
            ->setBegin(new \DateTime('2019-06-16 12:00:00'))
            ->setEnd(new \DateTime('2019-06-16 12:06:40'))
        ;

        $entries = [$timesheet, $timesheet2, $timesheet3, $timesheet4, $timesheet5, $timesheet6];

        $query = new TimesheetQuery();
        $query->setActivities([$activity]);
        $query->setBegin(new \DateTime());
        $query->setEnd(new \DateTime());
        $query->setProjects([$project]);

        return $renderer->render($entries, $query);
    }
}

class MetaFieldColumnSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            TimesheetMetaDisplayEvent::class => ['loadTimesheetField', 200],
            CustomerMetaDisplayEvent::class => ['loadCustomerField', 200],
            ProjectMetaDisplayEvent::class => ['loadProjectField', 200],
            ActivityMetaDisplayEvent::class => ['loadActivityField', 200],
        ];
    }

    public function loadTimesheetField(TimesheetMetaDisplayEvent $event): void
    {
        $event->addField($this->prepareEntity(new TimesheetMeta(), 'foo'));
        $event->addField($this->prepareEntity(new TimesheetMeta(), 'foo2'));
    }

    public function loadCustomerField(CustomerMetaDisplayEvent $event): void
    {
        $event->addField($this->prepareEntity(new CustomerMeta(), 'customer-foo'));
    }

    public function loadProjectField(ProjectMetaDisplayEvent $event): void
    {
        $event->addField($this->prepareEntity(new ProjectMeta(), 'project-foo'));
        $event->addField($this->prepareEntity(new ProjectMeta(), 'project-foo2')->setIsVisible(false));
    }

    public function loadActivityField(ActivityMetaDisplayEvent $event): void
    {
        $event->addField($this->prepareEntity(new ActivityMeta(), 'activity-foo'));
    }

    private function prepareEntity(MetaTableTypeInterface $meta, string $name): MetaTableTypeInterface
    {
        return $meta
            ->setLabel('Working place')
            ->setName($name)
            ->setType(TextType::class)
            ->setIsVisible(true);
    }
}
