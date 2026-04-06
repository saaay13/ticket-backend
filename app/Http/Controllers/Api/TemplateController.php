<?php

namespace App\Http\Controllers\Api;

use App\Models\Template;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Http\Resources\TemplateResource;

class TemplateController extends Controller
{
    public function index(Request $request)
    {
        $sort = $request->query('sort', 'name');
        $order = $request->query('order', 'asc');

        $templates = Template::orderBy($sort, $order)->get();
        return TemplateResource::collection($templates);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:templates',
            'type' => 'required|string|max:50',
            'content' => 'required|string',
            'metadata' => 'nullable|array',
            'created_by' => 'nullable|exists:users,id',
        ], [
            'name.required' => 'The name is required.',
            'name.unique' => 'A template with this name already exists.',
            'type.required' => 'The type is required.',
            'content.required' => 'The content is required.',
        ]);

        $template = Template::create($validated);
        
        return (new TemplateResource($template))
            ->additional(['message' => 'Template created successfully']);
    }

    public function show(Template $template)
    {
        return new TemplateResource($template);
    }

    public function update(Request $request, Template $template)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:templates,name,' . $template->id,
            'type' => 'required|string|max:50',
            'content' => 'required|string',
            'metadata' => 'nullable|array',
            'created_by' => 'nullable|exists:users,id',
        ], [
            'name.required' => 'The name is required.',
            'name.unique' => 'A template with this name already exists.',
            'type.required' => 'The type is required.',
            'content.required' => 'The content is required.',
        ]);

        $template->update($validated);
        
        return (new TemplateResource($template))
            ->additional(['message' => 'Template updated successfully']);
    }

    public function destroy(Template $template)
    {
        $template->delete();
        return response()->json(['message' => 'Template deleted successfully'], 200);
    }
}
