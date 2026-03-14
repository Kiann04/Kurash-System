<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <img
        src="/images/ksf-logo.png"
        :class="className"
        v-bind="$attrs"
        alt="Kurash System"
    />
</template>
