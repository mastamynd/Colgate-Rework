<script setup lang="ts">
import { updateTheme } from '@/composables/useAppearance';
import { Head, Link } from '@inertiajs/vue3';
import AppearanceToggle from '../components/AppearanceToggle.vue';
import MainMap from '../components/custom/MainMap.vue';
import { ref, onMounted, nextTick, computed } from 'vue';

const {api_key, counties, constituencies, wards, boundaries} = defineProps({
	api_key: String,
	counties: {
		type: Array,
		default: () => []
	},
	constituencies: {
		type: Array,
		default: () => []
	},
	wards: {
		type: Array,
		default: () => []
	},
	boundaries: {
		type: Promise,
		default: () => Promise.resolve([])
	},
	available_map_data: {
		type: Array,
		default: () => []
	}
});

const mapHeight = ref('900px');


onMounted(async () => {
	await nextTick();
	const header = document.querySelector('header');
	const form = document.body; // fallback to body as "form" container
	let headerHeight = 0;
	let formHeight = 0;

	if (header) {
		const headerRect = header.getBoundingClientRect();
		headerHeight = headerRect.height;
	}
	if (form) {
		const formRect = form.getBoundingClientRect();
		formHeight = formRect.height;
	}

	if (formHeight && headerHeight) {
		const calculated = formHeight - headerHeight - 30;
		mapHeight.value = calculated > 0 ? `${calculated}px` : '300px';
	}
});
</script>

<template>

	<Head title="Welcome">
	</Head>
	<div
		class="flex min-h-screen flex-col items-center bg-[#FDFDFC] p-2 text-[#1b1b18] dark:bg-[#0a0a0a] lg:justify-center lg:p-3">
		<header class="not-has-[nav]:hidden mb-2 w-full text-sm">
			<div class="flex items-center justify-between w-full mb-2">
				<!-- Logo on the left with Colgate themed background, rounded-full, and padding -->
				<div
					class="flex items-center justify-center rounded-full py-0.5 px-4 bg-gradient-to-r from-[#e41c23] via-[#e41c23] to-[#e41c23]/95 shadow-md"
					style="min-width: 40px; min-height: 45px;"
				>
					<img src="/logo.png" alt="Logo" class="h-8 w-auto rounded-full bg-[#e41c23] p-1" />
				</div>
				<!-- Navigation on the right -->
				<nav class="flex items-center justify-end gap-4">
					<template v-if="$page.props.auth.user">
						<img :src="`https://www.gravatar.com/avatar/${$page.props.auth.user.emailHash}?s=32&d=identicon`"
							alt="User Avatar" class="rounded-full w-8 h-8" />
						<span class="text-sm text-[#1b1b18] dark:text-[#EDEDEC]">
							{{ $page.props.auth.user.name }}
						</span>
						<Link :href="route('dashboard')"
							class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]">
						Dashboard
						</Link>
					</template>
					<template v-else>
						<Link :href="route('login')"
							class="inline-block rounded-sm border border-transparent px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#19140035] dark:text-[#EDEDEC] dark:hover:border-[#3E3E3A]">
						Log in
						</Link>
					</template>
					<AppearanceToggle></AppearanceToggle>
				</nav>
			</div>
		</header>
		<div
			class="duration-750 starting:opacity-0 flex w-full items-center justify-center opacity-100 transition-opacity lg:grow">
			<main class="flex w-full flex-col-reverse overflow-hidden rounded-lg lg:flex-row">
				<MainMap :api_key="api_key" :mapHeight="mapHeight" :counties="counties" :constituencies="constituencies" :wards="wards" :boundaries="boundaries" :available_map_data="available_map_data" />
			</main>
		</div>
		<div class="h-14.5 hidden lg:block"></div>
	</div>
</template>
