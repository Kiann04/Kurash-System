<script setup lang="ts">
import { type HTMLAttributes, computed, type Ref } from 'vue'
import {
  CalendarRoot,
  type CalendarRootEmits,
  type CalendarRootProps,
  useForwardPropsEmits,
  CalendarCell,
  CalendarCellTrigger,
  CalendarGrid,
  CalendarGridBody,
  CalendarGridHead,
  CalendarGridRow,
  CalendarHeadCell,
  CalendarHeader,
  CalendarNext,
  CalendarPrev,
} from 'reka-ui'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { ChevronLeft, ChevronRight } from 'lucide-vue-next'
import { cn } from '@/lib/utils'
import { useVModel } from '@vueuse/core'
import { DateFormatter, getLocalTimeZone, today, type DateValue } from '@internationalized/date'

const props = defineProps<CalendarRootProps & { class?: HTMLAttributes['class'] }>()

const emits = defineEmits<CalendarRootEmits>()

const delegatedProps = computed(() => {
  const { class: _, placeholder: __, ...delegated } = props

  return delegated
})

const placeholder = useVModel(props, 'placeholder', emits, {
  passive: true,
  defaultValue: (props.modelValue && !Array.isArray(props.modelValue) ? props.modelValue : undefined) ?? today(getLocalTimeZone()),
}) as Ref<DateValue | undefined>

const forwarded = useForwardPropsEmits(delegatedProps, emits)

const formatter = new DateFormatter('en-US', {
  month: 'long',
})

const months = computed(() => {
  const options = []
  for (let i = 0; i < 12; i++) {
    const date = today(getLocalTimeZone()).set({ month: i + 1 })
    options.push({
      value: (i + 1).toString(),
      label: formatter.format(date.toDate(getLocalTimeZone())),
    })
  }
  return options
})

const years = computed(() => {
  const currentYear = today(getLocalTimeZone()).year
  const startYear = currentYear - 100
  const endYear = currentYear + 100
  const options = []
  for (let i = startYear; i <= endYear; i++) {
    options.push({
      value: i.toString(),
      label: i.toString(),
    })
  }
  return options
})

const handleMonthChange = (value: string) => {
  if (!placeholder.value) return
  const newDate = placeholder.value.set({ month: parseInt(value) })
  placeholder.value = newDate
}

const handleYearChange = (value: string) => {
  if (!placeholder.value) return
  const newDate = placeholder.value.set({ year: parseInt(value) })
  placeholder.value = newDate
}
</script>

<template>
  <CalendarRoot
    v-slot="{ grid, weekDays }"
    v-model:placeholder="placeholder"
    :class="cn('p-3', props.class)"
    v-bind="forwarded"
  >
    <CalendarHeader class="relative flex w-full items-center justify-between pt-1">
      <CalendarPrev
        class="h-7 w-7 bg-transparent p-0 opacity-50 hover:opacity-100 flex items-center justify-center rounded-md border border-input hover:bg-accent hover:text-accent-foreground"
      >
        <ChevronLeft class="h-4 w-4" />
      </CalendarPrev>
      
      <div class="flex items-center gap-1">
          <Select
            :model-value="placeholder?.month.toString()"
            @update:model-value="(v) => handleMonthChange(v as string)"
          >
            <SelectTrigger class="h-7 border-none bg-transparent p-2 hover:bg-accent hover:text-accent-foreground focus:ring-0 focus:ring-offset-0 shadow-none font-medium text-sm gap-1">
              <SelectValue />
            </SelectTrigger>
            <SelectContent class="max-h-50">
              <SelectItem v-for="month in months" :key="month.value" :value="month.value">
                {{ month.label }}
              </SelectItem>
            </SelectContent>
          </Select>

          <Select
            :model-value="placeholder?.year.toString()"
            @update:model-value="(v) => handleYearChange(v as string)"
          >
            <SelectTrigger class="h-7 border-none bg-transparent p-2 hover:bg-accent hover:text-accent-foreground focus:ring-0 focus:ring-offset-0 shadow-none font-medium text-sm gap-1">
              <SelectValue />
            </SelectTrigger>
            <SelectContent class="max-h-50">
               <SelectItem v-for="year in years" :key="year.value" :value="year.value">
                {{ year.label }}
              </SelectItem>
            </SelectContent>
          </Select>
      </div>
      
      <CalendarNext
        class="h-7 w-7 bg-transparent p-0 opacity-50 hover:opacity-100 flex items-center justify-center rounded-md border border-input hover:bg-accent hover:text-accent-foreground"
      >
        <ChevronRight class="h-4 w-4" />
      </CalendarNext>
    </CalendarHeader>

    <div class="flex flex-col gap-y-4 mt-4 sm:flex-row sm:gap-x-4 sm:gap-y-0">
      <CalendarGrid v-for="month in grid" :key="month.value.toString()" class="w-full border-collapse space-y-1">
        <CalendarGridHead>
          <CalendarGridRow class="flex">
            <CalendarHeadCell
              v-for="day in weekDays"
              :key="day"
              class="text-muted-foreground rounded-md w-9 font-normal text-[0.8rem]"
            >
              {{ day }}
            </CalendarHeadCell>
          </CalendarGridRow>
        </CalendarGridHead>
        <CalendarGridBody>
          <CalendarGridRow
            v-for="(weekDates, index) in month.rows"
            :key="index"
            class="flex w-full mt-2"
          >
            <CalendarCell
              v-for="weekDate in weekDates"
              :key="weekDate.toString()"
              :date="weekDate"
              class="relative p-0 text-center text-sm focus-within:relative focus-within:z-20 [&:has([aria-selected])]:bg-accent first:[&:has([aria-selected])]:rounded-l-md last:[&:has([aria-selected])]:rounded-r-md"
            >
              <CalendarCellTrigger
                :day="weekDate"
                :month="month.value"
                class="h-9 w-9 p-0 font-normal aria-selected:opacity-100 flex items-center justify-center rounded-md hover:bg-accent hover:text-accent-foreground focus:bg-accent focus:text-accent-foreground aria-selected:bg-primary aria-selected:text-primary-foreground aria-selected:hover:bg-primary aria-selected:hover:text-primary-foreground aria-disabled:text-muted-foreground aria-disabled:opacity-50"
              />
            </CalendarCell>
          </CalendarGridRow>
        </CalendarGridBody>
      </CalendarGrid>
    </div>
  </CalendarRoot>
</template>
