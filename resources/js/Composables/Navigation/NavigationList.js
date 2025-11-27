import {usePage} from "@inertiajs/vue3";
import {
    ChessKnight, LayoutDashboard, LogOut,
    Megaphone,
    NotebookTabs,
    PhoneCall, PhoneMissed, PhoneOff, Pickaxe, Repeat,
    Trophy,
    UserRoundCheck,
    UserRoundX,
    Workflow
} from "lucide-vue-next";

export default function NavigationList() {
    const NavList = {
        main: [
            // {
            //     path: route('dashboard'),
            //     name: 'Dashboard',
            //     icon: LayoutDashboard,
            // },
            {
                path: route('web.active-calls.index'),
                name: 'Active Calls',
                icon: PhoneCall,
            },

            {
                path: route('web.competition.index'),
                name: 'Competitions',
                icon: Trophy,
            }
        ],
        terminated: [
            {
                path: route('web.participants.index'),
                name: 'Participants',
                icon: UserRoundCheck,
            },
            {
                path: route('web.entries.failed.index'),
                name: 'Non Entries',
                icon: UserRoundX,
            },
            {
                path: route('web.orphan-active-calls.index'),
                name: 'Orphaned Calls',
                icon: PhoneOff,
            },
        ],
        administration:[
            {
                path: route('web.organisations.index'),
                name: 'Organisations',
                icon: ChessKnight,
            },
            {
                path: route('web.phone-book-entries.index'),
                name: 'Phone Book',
                icon: NotebookTabs,
            },
        ],
        logs: [
            {
                path: route('web.api-request-logs.index'),
                name: 'API Request Logs',
                icon: Repeat,
            },
            {
                path: route('web.shout-request-logs.index'),
                name: 'Shout Audio API Logs',
                icon: Megaphone,
            },
            {
                path: route('web.max-capacity-call-logs.index'),
                name: 'Max Capacity Call Logs',
                icon: PhoneMissed,
            }
        ],
        docs: [
            {
                path: route('web.docs.call-flow'),
                name: 'Call Flow',
                icon: Workflow,
            }
        ],
        tools: [
            {
                path: '/horizon',
                name: 'Queues',
                icon: Pickaxe,
                target: '_blank',
            }
        ],
        settings: [
        ],
        user_navigation: [
            {
                path: route('logout'),
                name: 'Logout',
                icon: LogOut,
                method: 'post',
            }
        ]
    };

    const isNavActive = (item) => {
        const currentUrl = usePage().url.split('?')[0];

        return item.path.includes(currentUrl)
    };


    return {
        NavList,
        isNavActive
    }
}
