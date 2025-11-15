<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index()
    {
        return response()->json(Project::all());
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // validate image
            'link' => 'nullable|url',
        ]);

        $data = $request->only(['title', 'description', 'link']);
        $data['user_id'] = $request->user()->id;

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = time() . '_' . $file->getClientOriginalName();
            // Store the file on the 'public' disk in the 'projects' directory
            $path = $file->storeAs('projects', $filename, 'public'); // storage/app/public/projects
            $data['image'] = $path; // e.g. "projects/1600000000_my.jpg"
        }

        $project = Project::create($data);

        return response()->json($project, 201);
    }


    public function show(Project $project)
    {
        return response()->json($project);
    }

    public function update(Request $request, Project $project)
    {
        $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|string',
            'link' => 'nullable|url',
        ]);

        $project->update($request->only(['title', 'description', 'image', 'link']));

        return response()->json($project);
    }

    public function destroy(Project $project)
    {
        $project->delete();
        return response()->json(['message' => 'Project deleted successfully']);
    }
}
