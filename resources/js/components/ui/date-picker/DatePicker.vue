<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import {
  DateFormatter,
  type DateValue,
  getLocalTimeZone,
  parseDate,
  today,
  CalendarDate,
} from '@internationalized/date'
import { Calendar as CalendarIcon } from 'lucide-vue-next'
import { format } from 'date-fns'
import { cn } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import { Calendar } from '@/components/ui/calendar'
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from '@/components/ui/popover'

defineOptions({
  inheritAttrs: false,
})

const props = defineProps<{
  modelValue?: string | DateValue
  placeholder?: string
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', payload: string | undefined): void
}>()

const df = new DateFormatter('en-US', {
  dateStyle: 'long',
})

const value = ref<DateValue>()

watch(
  () => props.modelValue,
  (v) => {
    if (v) {
      if (typeof v === 'string') {
          try {
              value.value = parseDate(v)
          } catch (e) {
              // Handle invalid date string if necessary
              console.error("Invalid date string:", v)
          }
      } else {
        value.value = v
      }
    } else {
      value.value = undefined
    }
  },
  { immediate: true }
)

const handleSelect = (v: DateValue | undefined) => {
    value.value = v
    if (v) {
        emit('update:modelValue', v.toString())
    } else {
        emit('update:modelValue', undefined)
    }
}

// Convert DateValue to native Date for date-fns formatting
const toDate = (d: DateValue) => {
    const timeZone = getLocalTimeZone()
    return d.toDate(timeZone)
}
</script>

<template>
  <Popover>
    <PopoverTrigger as-child>
      <Button
        v-bind="$attrs"
        variant="outline"
        :class="cn(
          'w-full justify-start text-left font-normal',
          !value && 'text-muted-foreground',
          $attrs.class as string,
        )"
      >
        <CalendarIcon class="mr-2 h-4 w-4" />
        {{ value ? format(toDate(value), 'PPP') : placeholder || "Pick a date" }}
      </Button>
    </PopoverTrigger>
    <PopoverContent class="w-auto p-0">
      <Calendar
        v-model="value"
        initial-focus
        @update:model-value="handleSelect"
      />
    </PopoverContent>
  </Popover>
</template>
