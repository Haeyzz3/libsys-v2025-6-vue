import { ref, onMounted } from 'vue'
import { route } from 'ziggy-js'

interface UserStatistics {
    students: number
    staff: number
    faculty: number
    graduate_school: number
    admins: number
    total: number
}

export function useUserStatistics() {
    const statistics = ref<UserStatistics>({
        students: 0,
        staff: 0,
        faculty: 0,
        graduate_school: 0,
        admins: 0,
        total: 0
    })

    const loading = ref(false)
    const error = ref<string | null>(null)

    const fetchStatistics = async () => {
        loading.value = true
        error.value = null

        try {
            const response = await fetch(route('users.statistics'))

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`)
            }

            const data = await response.json()
            statistics.value = data
        } catch (err) {
            console.error('Failed to fetch user statistics:', err)
            error.value = err instanceof Error ? err.message : 'Failed to fetch statistics'
        } finally {
            loading.value = false
        }
    }

    // Auto-fetch on mount
    onMounted(() => {
        fetchStatistics()
    })

    return {
        statistics,
        loading,
        error,
        fetchStatistics,
        refetch: fetchStatistics
    }
}
