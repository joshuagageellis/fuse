/**
 * Simple error handling.
 */
export const handleErrors = (response: Response) => {
	if (!response.ok) {
		if (response.status === 400) {
			// Bad client request.
			return response.json().then((data) => {
				throw new Error(data.code);
			});
		}

		if (response.status === 401) {
			// Unauthorized.
			return response.json().then((data) => {
				throw new Error(data.code);
			});
		}

		throw new Error('Fail');
	}

	return response;
};
