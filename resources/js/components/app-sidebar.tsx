import { Link } from '@inertiajs/react';
import {
    BookMarked,
    BookOpen,
    CalendarDays,
    FolderGit2,
    GraduationCap,
    LayoutGrid,
    NotebookText,
    Library,
    Presentation,
    UserCog,
    Users,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { usePermissionCheck } from '@/hooks/use-can';
import {
    academicTerm,
    classroom,
    course,
    dashboard,
    enrolledSubject,
    student,
    subject,
    teacher,
    user,
} from '@/routes';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Cursos',
        href: course(),
        icon: GraduationCap,
        permission: 'courses.view',
    },
    {
        title: 'Matérias',
        href: subject(),
        icon: Library,
        permission: 'subjects.view',
    },
    {
        title: 'Períodos letivos',
        href: academicTerm(),
        icon: CalendarDays,
        permission: 'terms.view',
    },
    {
        title: 'Turmas',
        href: classroom(),
        icon: NotebookText,
        permission: 'classrooms.view',
    },
    {
        title: 'Minhas matérias',
        href: enrolledSubject(),
        icon: BookMarked,
        permission: 'subjects.view-enrolled',
    },
    {
        title: 'Alunos',
        href: student(),
        icon: Users,
        permission: 'students.view',
    },
    {
        title: 'Professores',
        href: teacher(),
        icon: Presentation,
        permission: 'teachers.view',
    },
    {
        title: 'Usuários',
        href: user(),
        icon: UserCog,
        permission: 'users.manage',
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/react-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#react',
        icon: BookOpen,
    },
];

export function AppSidebar() {
    const can = usePermissionCheck();
    const visibleNavItems = mainNavItems.filter(
        (item) => !item.permission || can(item.permission),
    );

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={visibleNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
