// app/composables/useApi.ts

export const useAuthToken = () =>
  useState<string | null>('authToken', () => null)

export const useApiFetch = async <T>(url: string, options: any = {}) => {
  const config = useRuntimeConfig()
  const token = useAuthToken()

  return await useFetch<T>(url, {
    baseURL: config.public.apiBase,
    ...options,
    headers: {
      ...(options.headers || {}),
      ...(token.value ? { Authorization: `Bearer ${token.value}` } : {}),
    },
  })
}
