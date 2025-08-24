import axios from 'axios';

export async function fetchBoundaries(boundariesToFetch: { boundary_type: string, boundary_id: string }) {
	try {
		const response = await axios.get(`/demarcations/fetch-geometry`, {
			params: {
				boundary_type: boundariesToFetch.boundary_type,
				boundary_id: boundariesToFetch.boundary_id
			}
		});

		const boundaries = response.data.map((boundary: any) => {
			return {
				id: boundary.id,
				name: boundary.name,
				geometry: boundary.geometry
			}
		});

		return boundaries;
	} catch (error) {
		console.error('Error fetching boundaries:', error);
		return [];
	}
}