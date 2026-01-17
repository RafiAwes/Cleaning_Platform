    public function getPackagePublic($id)
    {
        try {
            $package = Package::with(['services', 'addons', 'vendor'])
                ->findOrFail($id);

            return $this->successResponse($package, 'Package retrieved successfully', 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Package not found', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Error retrieving package: '.$e->getMessage(), 500);
        }
    }
